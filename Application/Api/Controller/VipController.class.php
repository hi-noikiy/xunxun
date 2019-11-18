<?php

namespace Api\Controller;
use Api\Service\MemberService;
use Api\Service\MemberVipService;
use Common\Util\RedisCache;
use Common\Util\ParamCheck;
use Think\Controller;
use Think\Log;


class VipController extends BaseController {

    /**用户购买vip功能接口
     * @param $token    token值
     * @param $need_coin    虚拟币
     * @param $long_day vip时长
     * @param $is_active    状态 1续费vip 2激活vip
     * @param $signature    签名(md5(strtolower(token+is_active)))
     * 接口说明:
     */
    public function chargeVip($token,$need_coin,$long_day,$is_active,$signature){
        $data = [
            "token" => I('post.token'),
            "need_coin" => I('post.need_coin'),
            "long_day" => I('post.long_day'),
            "is_active" => I('post.is_active'),
            "signature" => I('post.signature'),
        ];
        try{
            //验签数据
            if($data['signature']!== md5(strtolower($data['token'].$data['is_active']))){
                E("验签失败",2000);
            }
            //校验数据
            ParamCheck::checkInt("long_day",$data['long_day'],1);
            ParamCheck::checkInt("is_active",$data['is_active'],1);
            //判断当前的时长是否正确
            $buy_vip_time = D('siteconfig')->getField("buy_vip_time");
            if($data['long_day'] !== $buy_vip_time){
                E("购买时长数据有异常",2000);
            }
            //获取用户值
            $user_id = RedisCache::getInstance()->get($data['token']);
            $id = D('member')->getOneById($user_id);
            if(!$id){
                E("该用户不存在", 5002);
            }
            //用户购买vip操作
            /*if($data['is_active'] ==1){     //续费vip
                //判断当前用户是否购买过vip功能,如果当前用户购买过此vip功能,并且没有锁定,那么他的时间是叠加的
                $vip_data = VipDetailService::getInstance()->findOneById($user_id);
                if($vip_data){
                    //修改当前vip数据(累计增加时长和修改到期时间)
                    $days =$vip_data['long_day'] +  $data['long_day'];
                    $expires_time= date("Y-m-d H:i:s",strtotime($vip_data['create_time']."+$days days"));
                    $result = VipDetailService::getInstance()->updateVip($user_id,$days,$expires_time);
                }else{
                    //没有购买过vip,这里就直接增加一条数据
                    $days = $data['long_day'];
                    $create_time = date('Y-m-d H:i:s',time());
                    $dataes = [
                        "user_id" => $user_id,
                        "create_time" => $create_time,
                        "long_day" => $days,
                        "expires_time" => date("Y-m-d H:i:s",strtotime($create_time."+$days days")),        //转换时间
                    ];
                    $result = VipDetailService::getInstance()->addData($dataes);
                }

            }else{      //激活vip,如果当前用户是激活则修改对应的起始时间与购买时长及结束时间
                $days = $data['long_day'];
                $create_time = date('Y-m-d H:i:s',time());
                $dataes = [
                    "create_time" => $create_time,
                    "long_day" => $days,
                    "expires_time" => date("Y-m-d H:i:s",strtotime($create_time."+$days days")),        //转换时间
                ];
                $result =  VipDetailService::getInstance()->avtiveVip($user_id,$dataes);
            }*/
            if($data['is_active'] ==1){     //续费vip
                //如果用户是续费,他所产生的虚拟币也是增加经验值的,然后将虚拟币对应的消耗M豆的计算方法剩下的M豆加在usesp_unit里面，
                //例子:消耗230豆,每消耗100豆,增加200经验值,将剩下的30豆加在用户的useup_unit里面,当下次消费的70豆加上useup_uint大于等于100豆，在增加经验值
                //获取用户的剩余值
                $useup_unit = D('member')->getOneByIdField($user_id,"useup_unit");
                $exp_admin_values = D('member')->getOneByIdField($user_id,"exp_admin_values");      //取得经验值
                //获取当前提升等级制度数据(消耗M豆)
                /* $num = 355;
                 if($num%100 == 0 ){
                     echo "123";
                 }else{
                     var_dump($num%100);
                 }
                 die();*/
//                var_dump($useup_unit);
                $type = 2;
                $vipexp_data = D('VipExp')->detail($type);
                if(($data['need_coin'] + $useup_unit)%$vipexp_data['exp_nuit'] !== 0){    //取余值
                    $number = ($data['need_coin'] + $useup_unit)%$vipexp_data['exp_nuit'];      //取余值
//                    var_dump($number);
                    $update = [
                        "useup_unit" => $number,      //改变剩余虚拟币
                    ];
                    D('member')->updateDate($user_id,$update);
                    $number_exp_values = floor(($data['need_coin'] + $useup_unit)/$vipexp_data['exp_nuit']);     //求出计算单位的几倍值
                    /*var_dump($number_exp_values);die();*/
                    $update = [
                        "exp_admin_values" => $exp_admin_values + ($number_exp_values*$vipexp_data['exp_values']),       //改变经验值
                    ];
//                    var_dump($update);die();
                    D('member')->updateDate($user_id,$update);
                }else{
                    $number = ($data['need_coin'] + $useup_unit)%$vipexp_data['exp_nuit'];
                    $number_exp_values = floor(($data['need_coin'] + $useup_unit)/$vipexp_data['exp_nuit']);     //求出计算单位的几倍值
                    $update = [
                        "useup_unit" => 0,
                        "exp_admin_values" => $exp_admin_values + ($number_exp_values*$vipexp_data['exp_values']),       //改变经验值
                    ];
                    D('member')->updateDate($user_id,$update);
                }
                //判断当前用户是否购买过vip功能,如果当前用户购买过此vip功能,并且没有锁定,那么他的时间是叠加的
                $long_days = D('member')->getOneByIdField($user_id,"long_day");
                if($long_days){
                    //修改当前vip数据(累计增加时长)
                    $long_day = $long_days +  $data['long_day'];
                    $update = [
                        "long_day" => $long_day,
                    ];
                    $result = D('member')->updateDate($user_id,$update);
                }else{
                    //没有购买过vip,这里就直接增加一条数据
                    $long_day = $data['long_day'];
                    $vip_buytime = date('Y-m-d H:i:s',time());
                    $update = [
                        "long_day" => $long_day,
                        "vip_buytime" => $vip_buytime,
                    ];
                    $result = D('member')->updateDate($user_id,$update);
                }

            }else{      //激活vip,如果当前用户是激活则修改对应的起始时间与购买时长
                $days = $data['long_day'];
                $vip_buytime = date('Y-m-d H:i:s',time());
                $update = [
                    "long_day" => $days,
                    "vip_buytime" => $vip_buytime,
                ];
                $result = D('member')->updateDate($user_id,$update);
            }
            $coindetail_data = [];      //消费数组
            $user_info = MemberService::getInstance()->UserDetail($user_id);
            $user_info['totalcoin']=$user_info['totalcoin']-$user_info['freecoin'];
            if($result){
                //加入用户消费记录里
                $coindetail_data['action'] = "vip";
                $coindetail_data['room_id'] = "0";
                $coindetail_data['uid'] = $user_id;
                $coindetail_data['touid'] = $user_id;
                $coindetail_data['giftid'] = 0;
                $coindetail_data['giftcount'] = 0;
                $coindetail_data['content'] = "vip购买";
                $coindetail_data['coin'] = $data['need_coin'];
                $coindetail_data['coin_before'] = $user_info['totalcoin'];
                $coindetail_data['coin_after'] = $user_info['totalcoin'] - $data['need_coin'];
                $coindetail_data['addtime'] = date('Y-m-d H:i:s',time());
                $coindetail_data['status'] = 1;
                D("Coindetail")->add($coindetail_data);
            }
            //更新用户表的数据
            D('member')->where(array("id"=>$user_id))->setInc('freecoin',$data['need_coin']);
            $updatevip['is_vip'] = 1;   //会员状态
            D('member')->updateDate($user_id,$updatevip);
            //购买成功后将购买数据返回去
            $user_info = D('member')->user_vipinfo($user_id);
            $user_info['is_active'] = $data['is_active'];
            $result = [
                "user_info" => dealnull($user_info),
            ];
            //查询成功
            $this -> returnCode = 200;
            $this -> returnData = $result;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

    }

    /**vip 用户数据
     * @param $token    token值
     * @param $signature    签名(md5(strtolower(token)))
     */
    public function getList($token,$signature){
        //获取数据
        $data = [
            "token" => I('post.token'),
            "signature" => I('post.signature'),
        ];
        try{
            //验签数据
            /*if($data['signature']!== md5(strtolower($data['token']))){
                E("验签失败",2000);
            }*/
            //获取用户值
            $user_id = RedisCache::getInstance()->get($data['token']);
//            var_dump($user_id);die();
            $user_info = D("Member")->user_vipinfo($user_id);
            if(empty($user_info['user_id'])){
                E("当前用户不存在",2000);
            }
            $user_info['avatar'] = MemberService::getInstance()->getOneByIdField($user_id,"avatar");       //用户头像
            $user_info['avatar'] = getavatar($user_info['avatar']);
//            var_dump($user_info);die();
            //判断当前用户是否购买过vip(当前时间-购买时间大于当前时长长,表示vip,反之让用户激活)
            $end_time = date('Y-m-d H:i:s',time());
            $cnt = strtotime($end_time) - strtotime($user_info['vip_buytime']);
            $cnt = floor($cnt/(3600*24));       //算出天数
            if(empty($user_info['vip_buytime']) || empty($user_info['long_day'])){
                $user_info['vip_status'] = 0;       //当前用户为非vip会员状态
            }else if($cnt>$user_info['long_day']){
                $user_info['vip_status'] = 2;       //需要当前用户激活状态
            }else{
                $user_info['vip_status'] = 1;       //当前用户为vip会员状态
            }
            /* $rs['sum'] = '4'; //总数
             $rs['row'] = '100'; //单个数
             echo round($rs['row']/$rs['sum']*100)."％";
             die();*/
           /* //查找所有用户经验值数据，求当前用户百分比开始
            $all_expvalue = D("member")->all_exp();
            $all_expvalue = array_column($all_expvalue, 'total_expvalue');   //二维数组转化为一维数组
//            var_dump($all_expvalue);die();
            $row = count($all_expvalue);        //统计个数
            $num = 100;
            $percentage = round($num/$row*100/100)."%";    //每个值的百分比
            //通过二分查找出当前用户的第几位，每一位值为$percentage这个百分比
            $total_expvalue = D("member")->merge_exp($user_id);     //统计当前用户的经验值
            sort($all_expvalue);        //排序
            $percentages = $this->binSearch($all_expvalue,$total_expvalue['total_expvalue'])+1;
            //求当前用户百分比结束*/
            //修改开始,查找所有用户经验值数据，求当前用户百分比开始
            $all_expvalue = D("member")->all_exp();
            $all_expvalue = array_column($all_expvalue, 'total_expvalue');   //二维数组转化为一维数组
//            var_dump(array_merge($all_expvalue));die();
            arsort($all_expvalue);        //保持键/值对的逆序排序函数
            $vip_exp = array_flip($all_expvalue);   //反转数组
            $total_expvalue = D("member")->merge_exp($user_id);     //统计当前用户的经验值
            $vip_sort = $this->vipfun($total_expvalue['total_expvalue'],$vip_exp);        //当前第多少列位
            $percentages = intval($vip_sort/current($vip_exp)*100)."%"; //百分比数据
            //修改结束

            //根据经验值,来获取对应的vip等级
            $vip_dengji = vip_grade($total_expvalue['total_expvalue']);
            $user_info['vip_dengji'] = $vip_dengji;     //vip等级
            $user_info['vip_image'] = C("APP_URL").D("member_vip")->getOneByIdField($user_info['vip_dengji'],"vip_image");     //下一个等级经验值
            $user_info['vip_next_dengji'] = $vip_dengji+1;      //下一个vip等级
            $user_info['exp_values'] = $total_expvalue['total_expvalue'];     //当前等级经验值
            $user_info['exp_next_values'] = D("member_vip")->getOneByIdField($user_info['vip_next_dengji'],"exp");     //下一个等级经验值
//            $user_info['percentages'] = $percentages*$percentage;       //占本应用所有百分比
            $user_info['percentages'] = $percentages;       //占本应用所有百分比
           /* $vip_list = MemberVipService::getInstance()->getList();
            foreach($vip_list as $key=>$value){
                $vip_list[$key]['vip_image'] = C("APP_URL").$value['vip_image'];
            }*/
            $vip_chargelist = D('vip')->getList();
            $result = [
                "user_info" => $user_info,
                "vip_chargelist" => $vip_chargelist,
            ];
            //查询成功
            $this -> returnCode = 200;
            $this -> returnData = $result;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();
    }

    //用户等级方法
    private function vipfun($gf,$arr)//用户等级函数
    {
        foreach ($arr as $key => $value)
        {
            if ($gf >= $key)
            {
                return $value;
            }
        }
    }

    //二分查找法
    private function binSearch($arr,$search){
        $height=count($arr)-1;
        $low=0;
        while($low<=$height){
            $mid=floor(($low+$height)/2);//获取中间数
            if($arr[$mid]==$search){
                return $mid;//返回
            }elseif($arr[$mid]<$search){//当中间值小于所查值时，则$mid左边的值都小于$search，此时要将$mid赋值给$low
                $low=$mid+1;
            }elseif($arr[$mid]>$search){//中间值大于所查值,则$mid右边的所有值都大于$search,此时要将$mid赋值给$height
                $height=$mid-1;
            }
        }
        return false;
    }
    /**公用方法
     * @param $rmb      充值金额
     * @param $long_day  充值会员时间长按30天计算,年的按365天走
     * @param $is_active    状态 1续费vip 2激活vip
     */
    public static function chargeVipes($rmb,$user_id,$long_day,$is_active){
        $data['rmb'] = $rmb;
        $data['long_day'] = $long_day;
        if($data['long_day'] == 12){
            $data['long_day'] = 365;
        }else{
            $data['long_day'] = $long_day*30;
        }
        $data['is_active'] = $is_active;
//        try{
        //验签数据
        /*if($data['signature']!== md5(strtolower($data['token']+$data['is_active']))){
            E("验签失败",2000);
        }*/
        //校验数据
        ParamCheck::checkInt("long_day",$data['long_day'],1);
        ParamCheck::checkInt("is_active",$data['is_active'],1);
        if($data['is_active'] ==1){     //续费vip
            //充值RMB的计算单位值
            $char_unit = D('member')->getOneByIdField($user_id,"char_unit");
            $exp_admin_values = D('member')->getOneByIdField($user_id,"exp_admin_values");      //取得经验值
            /* $num = 355;
             if($num%100 == 0 ){
                 echo "123";
             }else{
                 var_dump($num%100);
             }
             die();*/
            $type = 1;          //vip 成长类型 1充值 2消耗M豆 3获取钻石 4 房间在线时间',
            $vipexp_data = D('VipExp')->detail($type);
            if(($data['rmb'] + $char_unit)%$vipexp_data['exp_nuit'] !== 0){    //取余值
                $number = ($data['rmb'] + $char_unit)%$vipexp_data['exp_nuit'];      //取余值
                $update = [
                    "char_unit" => $number,      //改变剩余充值金额
                ];
                Log::record("char_unit". json_encode(json_encode($update)), "INFO" );
                D('member')->updateDate($user_id,$update);
                $number_exp_values = floor(($data['rmb'] + $char_unit)/$vipexp_data['exp_nuit']);     //求出计算单位的几倍值
                $update = [
                    "exp_admin_values" => $exp_admin_values + ($number_exp_values*$vipexp_data['exp_values']),       //改变经验值
                ];
//                    var_dump($update);die();
                D('member')->updateDate($user_id,$update);
            }else{
                $number = ($data['rmb'] + $char_unit)%$vipexp_data['exp_nuit'];
                $number_exp_values = floor(($data['rmb'] + $char_unit)/$vipexp_data['exp_nuit']);     //求出计算单位的几倍值
                $update = [
                    "char_unit" => 0,
                    "exp_admin_values" => $exp_admin_values + ($number_exp_values*$vipexp_data['exp_values']),       //改变经验值
                ];
                D('member')->updateDate($user_id,$update);
            }
            //判断当前用户是否购买过vip功能,如果当前用户购买过此vip功能,并且没有锁定,那么他的时间是叠加的
            $long_days = D('member')->getOneByIdField($user_id,"long_day");
            if($long_days){
                //修改当前vip数据(累计增加时长)
                $long_day = $long_days +  $data['long_day'];
                $update = [
                    "long_day" => $long_day,
                ];
                $result = D('member')->updateDate($user_id,$update);
            }else{
                //没有购买过vip,这里就直接增加一条数据
                $long_day = $data['long_day'];
                $vip_buytime = date('Y-m-d H:i:s',time());
                $update = [
                    "long_day" => $long_day,
                    "vip_buytime" => $vip_buytime,
                ];
                $result = D('member')->updateDate($user_id,$update);
            }

        }else{      //激活vip,如果当前用户是激活则修改对应的起始时间与购买时长
            $days = $data['long_day'];
            $vip_buytime = date('Y-m-d H:i:s',time());
            $update = [
                "long_day" => $days,
                "vip_buytime" => $vip_buytime,
            ];
            $result = D('member')->updateDate($user_id,$update);
        }
        $coindetail_data = [];      //消费数组
        $user_info = MemberService::getInstance()->UserDetail($user_id);
        if($result){
            //加入用户消费记录里
            $coindetail_data['action'] = "vip";
            $coindetail_data['room_id'] = "0";
            $coindetail_data['uid'] = $user_id;
            $coindetail_data['touid'] = $user_id;
            $coindetail_data['giftid'] = 0;
            $coindetail_data['giftcount'] = 0;
            $coindetail_data['content'] = "vip购买";
            $coindetail_data['coin'] = $data['long_day'];
            $coindetail_data['coin_before'] = $user_info['totalcoin'];
            $coindetail_data['coin_after'] = $user_info['totalcoin'] - $data['need_coin'];
            $coindetail_data['addtime'] = date('Y-m-d H:i:s',time());
            $coindetail_data['status'] = 3;     //1虚拟币 2钻石 3vip充值
            D("Coindetail")->add($coindetail_data);
        }
        $updatevip['is_vip'] = 1;
        D('member')->updateDate($user_id,$updatevip);
        return $result;
        //购买成功后将购买数据返回去
        /* $user_info = D('member')->user_vipinfo($user_id);
         $user_info['is_active'] = $data['is_active'];
         $result = [
             "user_info" => dealnull($user_info),
         ];*/
        //查询成功
        /*      $this -> returnCode = 200;
              $this -> returnData = $result;
          }catch(\Exception $e){
              $this -> returnCode = $e ->getCode();
              $this -> returnMsg = $e ->getMessage();
          }
          $this->returnData();*/

    }

    /**当前用户为vip时,充值就会增加经验值
     * @param $user_id  用户id
     * @param $types    类型
     * @param $where
     */
    public static function vipupdate($user_id,$types,$where){
        $user_info = D("Member")->user_vipinfo($user_id);
        $end_time = date('Y-m-d H:i:s',time());
        $cnt = strtotime($end_time) - strtotime($user_info['vip_buytime']);
        $cnt = floor($cnt/(3600*24));       //算出天数
        if($types == 1 && $cnt<$user_info['long_day']){
            //根据用户id获取char_unit
            $type = 1;      //1是充值
            $vipexp_data = D('VipExp')->detail($type);
            $exp_admin_values = D('member')->getOneByIdField($user_id,"exp_admin_values");      //取得经验值
            $rmb = M('chargedetail')->where($where)->getField("rmb");
            $char_unit = M('member')->where(array("id"=>$user_id))->getField("char_unit");
            if(($rmb + $char_unit)%$vipexp_data['exp_nuit'] !== 0){
                $number = ($rmb + $char_unit)%$vipexp_data['exp_nuit'];      //取余值
                $update = [
                    "char_unit" => $number,      //改变剩余虚拟币
                ];
                D('member')->updateDate($user_id,$update);
                $number_exp_values = floor(($rmb + $char_unit)/$vipexp_data['exp_nuit']);     //求出计算单位的几倍值
                $update = [
                    "exp_admin_values" => $exp_admin_values + ($number_exp_values*$vipexp_data['exp_values']),       //改变经验值
                ];
//                    var_dump($update);die();
                D('member')->updateDate($user_id,$update);
            }else{
                $number = ($rmb + $char_unit)%$vipexp_data['exp_nuit'];
                $number_exp_values = floor(($rmb + $char_unit)/$vipexp_data['exp_nuit']);     //求出计算单位的几倍值
                $update = [
                    "char_unit" => 0,
                    "exp_admin_values" => $exp_admin_values + ($number_exp_values*$vipexp_data['exp_values']),       //改变经验值
                ];
                D('member')->updateDate($user_id,$update);
            }
        }
    }

    /**vip充值列表接口
     * @param $token    token值
     * @param $signature    验签md5(strtolower(token))
     */
    public function vip_list($token,$signature){
        //获取数据值
        $data = [
            "token" => I("post.token"),
            "signature" => I('post.signature'),
        ];
        try{
            //验签数据
            /*if($data['signature']!== md5(strtolower($data['token']))){
                E("验签失败",2000);
            }*/
            //vip充值列表后台配置数据
            $vip_chargelist = D('vip')->getList();
            $result = [
                "vip_chargelist" => $vip_chargelist,
            ];
            //查询成功
            $this -> returnCode = 200;
            $this -> returnData = $result;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();
    }




}


?>