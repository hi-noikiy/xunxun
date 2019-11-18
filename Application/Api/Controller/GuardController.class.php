<?php

namespace Api\Controller;
use Api\Service\MemberService;
use Api\Service\GuardValuesService;
use Api\Service\GuardService;
use Api\Service\LanguageroomService;
use Common\Util\RedisCache;
use Common\Util\ParamCheck;
use Think\Controller;


class GuardController extends BaseController {

    /**守护房间和用户列表接口
     * @param $token    token值
     * @param $signature    签名(md5(strtolower(token)))
     */
	public function getList($token,$signature){
        $data = [
            "token" => I('post.token'),
            "signature" => I('post.signature'),
        ];
		try{
            //校验数据
            if($data['signature']!== md5(strtolower($data['token']))){
                E("验签失败",2000);
            }
            //通过token获取用户信息
            $user_id = RedisCache::getInstance()->get($data['token']);
//            var_dump($user_id);die();
			//获取所有守护房间数据
            $guard_roomlist = GuardValuesService::getInstance()->getGuardRoom($user_id);
            foreach($guard_roomlist as $key=>$value){
                $guard_roomlist[$key]['room_name'] = LanguageroomService::getInstance()->getOneByIdField($value['room_id'],"room_name");  //房间名称
                $guard_roomlist[$key]['image'] = C('APP_URL').$value['image'];                  //守护图标
                //根据房间获取创建人的用户头像
                $room_user['user_id'] = LanguageroomService::getInstance() -> getOneByIdField($value['room_id'],"user_id");
                $guard_roomlist[$key]['room_image'] = MemberService::getInstance()->getOneByIdField($room_user['user_id'],"avatar");       //房间头像(用户头像)
                $guard_roomlist[$key]['room_image'] = getavatar($guard_roomlist[$key]['room_image']);
                //获取当前房间的分类
                $values['room_type'] = LanguageroomService::getInstance() -> getOneByIdField($value['room_id'],"room_type");
                $guard_roomlist[$key]['room_type'] = D('RoomMode')->getOneByIdField($values['room_type'],"room_mode");       //房间分类属性
                $days = $value['long_day'];     //时间赋值
                $end_time  = date("Y-m-d H:i:s",strtotime($value["creat_time"]."+$days days"));       //转换时间
                $start_time = date('Y-m-d H:i:s',time());
                $guard_roomlist[$key]['countdown_day'] = $this->datetoday($end_time,$start_time);          //剩余时间
                $guard_roomlist[$key]['gold_marks'] = $this->yellow_gold($value['long_day']);         //金银铜标识
            }
            if(empty($guard_roomlist)){
                $guard_roomlist = [];
            }
            //获取所有守护个人数据
            $guard_memberlist = GuardValuesService::getInstance()->getGuardMember($user_id);
            foreach($guard_memberlist as $key=>$value){
                $guard_memberlist[$key]['nickname'] = MemberService::getInstance()->getOneByIdField($value['user_id'],"nickname");  //用户名称
                $guard_memberlist[$key]['image'] = C('APP_URL').$value['image'];                  //守护图标
                //根据房间获取创建人的用户头像
                $guard_memberlist[$key]['avatar'] = MemberService::getInstance()->getOneByIdField($value['user_id'],"avatar");       //房间头像(用户头像)
                $guard_memberlist[$key]['avatar'] = getavatar($guard_memberlist[$key]['avatar']);
                $days = $value['long_day'];     //时间赋值
                $end_time  = date("Y-m-d H:i:s",strtotime($value["creat_time"]."+$days days"));       //转换时间
                $start_time = date('Y-m-d H:i:s',time());
                $guard_memberlist[$key]['countdown_day'] = $this->datetoday($end_time,$start_time);          //剩余时间
                $guard_memberlist[$key]['gold_marks'] = $this->yellow_gold($value['long_day']);         //金银铜标识
            }
            if(empty($guard_memberlist)){
                $guard_memberlist = [];
            }
			//查询成功
            $result = [
                "guard_roomlist" => $guard_roomlist,
                "guard_memberlist" => $guard_memberlist,
            ];
			$this -> returnCode = 200;
			$this -> returnData = $result;
		}catch(\Exception $e){
			$this -> returnCode = $e ->getCode();
			$this -> returnMsg = $e ->getMessage();
		}
		$this->returnData();

	}

    /**时间差函数方法
     * @param $end_time     结束时间
     * @param $start_time   开始时间
     */
    private function datetoday($end_time,$start_time){
        $Date_List_a1=explode("-",$end_time);
        $Date_List_a2=explode("-",$start_time);
        $d1=mktime(0,0,0,$Date_List_a1[1],$Date_List_a1[2],$Date_List_a1[0]);
        $d2=mktime(0,0,0,$Date_List_a2[1],$Date_List_a2[2],$Date_List_a2[0]);
        $Days=round(($d1-$d2)/3600/24);
        if($Days<=0){
            $Days = 0;
        }
        return $Days;
    }

    /**会员守护房间或者用户详情
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     * @param $guard_level  守护房间或者用户等级(守护配置的id)
     * @param $countdown_day    守护时长
     * @param $gold_marks       标识属性 1铜 2银 3金
     * @param $signature    签名(md5(strtolower($token+$countdown_day)))
     */
    public function guard_info($token,$room_id=null,$user_id=null,$guard_level,$countdown_day,$gold_marks,$signature){
        //获取数据
        $data = [
            "token" => I('post.token'),
            "room_id" => I('post.room_id'),
            "user_id" => I('post.user_id'),
            "guard_id" => I('post.guard_level'),
            "countdown_day" => I('post.countdown_day'),
            "gold_marks" => I('post.gold_marks'),
            "signature" => I('post.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("guard_level",$data['guard_id'],1);
            ParamCheck::checkInt("countdown_day",$data['countdown_day'],1);
            //验签数据
            if($data['signature'] !== Md5(strtolower($data['token'].$data['countdown_day']))){
                E("验签失败",2000);
            }
            //获取名称
            if($data['room_id']){
                $name = LanguageroomService::getInstance() -> getOneByIdField($data['room_id'],"room_name");
            }
            //用户名称
            if($data['user_id']){
                $name =  MemberService::getInstance()->getOneByIdField($data['user_id'],"nickname");
            }
//            var_dump($name);die();
            //获取当前房间或者用户守护等级详情
            $guard_info = D('Guard')->getGuardfind($data['guard_id']);
            $guard_info['image'] =  C("APP_URL").$guard_info['image']; //当前等级
            $guard_info['name'] = $name;            //房间或者用户名称
            $guard_info['room_id'] = $data['room_id'];      //房间id
            $guard_info['user_id'] = $data['user_id'];      //用户id
            $guard_info['countdown_day'] = $data['countdown_day'];      //守护时长长
            $guard_info['gold_marks'] = $data['gold_marks'];      //标识属性 1铜 2银 3金
            $result = [
                "guard_info" => $guard_info,
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

    /**续费守护房间或者用户中心数据
     * @param $token    token值
     * @param null $room_id     房间id
     * @param null $user_id     用户id
     * @param $guard_level      守护配置id
     * @param $countdown_day    守护时长
     * @param $type             守护类型 0 守护房间 1守护个人
     * @param null $signature      签名(md5(strtolower($token+$countdown_day)))
     */
    public function guard_change($token,$room_id=null,$user_id=null,$guard_level,$countdown_day,$type,$signature){
        //获取数据
        $data = [
            "token" => I('post.token'),
            "room_id" => I('post.room_id'),
            "user_id" => I('post.user_id'),
            "guard_id" => I('post.guard_level'),
            "countdown_day" => I('post.countdown_day'),
            "type" => I('post.type'),
            "signature" => I('post.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("guard_level",$data['guard_id'],1);
            ParamCheck::checkInt("countdown_day",$data['countdown_day'],1);
            //验签数据
            if($data['signature'] !== Md5(strtolower($data['token'].$data['countdown_day']))){
                E("验签失败",2000);
            }
            //获取名称
            if($data['room_id']){
                $name = LanguageroomService::getInstance() -> getOneByIdField($data['room_id'],"room_name");
            }
            //用户名称
            if($data['user_id']){
                $name =  MemberService::getInstance()->getOneByIdField($data['user_id'],"nickname");
            }
//            var_dump($name);die();
            //获取用户或者房间的中心数据
            $guard_info = [];
            $guard_image = D('Guard')->getOneByIdField($data['guard_id'],"image");
            $guard_info['name'] = $name;            //房间或者用户名称
            $guard_info['image'] = C("APP_URL").$guard_image; //当前守护图标
            $guard_info['room_id'] = $data['room_id'];      //房间id
            $guard_info['user_id'] = $data['user_id'];      //用户id
            $guard_info['countdown_day'] = $data['countdown_day'];      //守护时长长
            //获取续费守护房间或者用户守护所有等级详情
            $guard_listes = D('Guard')->getGuardlist($data['type']);
            foreach($guard_listes as $key=>$value){
                $guard_listes[$key]['image'] = C("APP_URL").$value['image']; //当前守护图标
            }

            $result = [
                "guard_info" => $guard_info,
                "guard_list" => $guard_listes,
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

    /**金银铜封装方法
     * @param $long_time
     * retunr $marks 1 铜 2银 3金 7天表示铜 小于30都属于铜 大于3天小于123都表示银 大于等于120属性金
     */
    private function yellow_gold($long_time){
        $marks = [];
        if($long_time<=7){  //铜
            $marks = 1;
        }else if($long_time>7 && $long_time<30){       //铜
            $marks = 2;
        }else if($long_time>=30 && $long_time<120){      //银
            $marks = 2;
        }else if($long_time>=120){
            $marks = 3;
        }
        return $marks;
    }

    /**获取所有守护列表信息
     * @param $token    token值
     * @param $signature    签名(md5(strtolower($token)))
     * 接口说明:根据用户的守护时长来进行排序
     */
    public function All_list($token,$signature){
        //获取数据
        $data = [
            "token" => I('post.token'),
            "signature" => I('post.signature'),
        ];
        try{
            //验签数据
            /* if($data['signature'] !== Md5(strtolower($data['token']))){
                 E("验签失败",2000);
             }*/
            //通过token获取用户信息
            $user_id = RedisCache::getInstance()->get($data['token']);
            //获取当前用户守护所有的数据列表(包括个人与房间守护)
            $guard_list = GuardValuesService::getInstance()->getList($user_id);
            if($guard_list){
                foreach($guard_list as $key=>$value){
                    $guard_list[$key]['sort'] = $key+1;     //数组排序
                    if($value['type'] == 0){    //守护房间
                        $guard_list[$key]['name'] =LanguageroomService::getInstance() -> getOneByIdField($value['target_id'],"room_name");
                    }else{
                        $guard_list[$key]['name']  =  MemberService::getInstance()->getOneByIdField($value['target_id'],"nickname");
                    }
                    $guard_list[$key]['image'] =  C("APP_URL").D('Guard')->getOneByIdField($value['guard_level'],"image");
                    $guard_list[$key]['gold_marks'] =  D('Guard')->getOneByIdField($value['guard_level'],"gold_marks");
                    unset($guard_list[$key]['guard_level']);
                }

            }else{
                $guard_list = [];
            }

            $result = [
                "guard_list" => $guard_list,
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