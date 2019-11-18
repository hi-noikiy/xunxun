<?php
namespace Api\Controller;

use Api\Service\VisitorMemberService;
use Api\Service\CoindetailService;
use Api\Service\MemberService;
use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Think\Log;

class VisitorMemberController extends BaseController {

    /**
     * 最近访客历史记录
     * @param $token token值
     * @param $signature 签名MD5(小写） token+page
     */
    public function getList($token,$page,$signature){
    //获取数据
        $data = [
            "token" => I('post.token'),
            "page" => I('post.page'),
            "signature" => I('post.signature'),
        ];
        $uid = RedisCache::getInstance()->get($data['token']);
//        $uid = "1031863";
//        var_dump($uid);die();
        try{
//             //校验数据
//             ParamCheck::checkInt("page",$data['page'],1);
//             /*if($data['signature'] !== Md5(strtolower($data['token'].$data['page']))){
//                 E("验签失败",2000);
//             }*/
//             // 每页条数
//             $size = 20;
//             $pageNum = empty($size)?20:$size;
//             $page = empty($data['page'])?1:$data['page'];
//             $where['touid'] = $uid;
//             $count =VisitorMemberService::getInstance()->countes($where);
//             //修改查看用户的状态
//             VisitorMemberService::getInstance()->updateStatus($where);
// //            var_dump($count);die();
//             // 总页数.
//             $totalPage = ceil($count/$pageNum);
//             // 页数信息.
//             $pageInfo = array("page" => $page, "pageNum"=>$pageNum, "totalPage" => $totalPage);
//             $limit = ($page-1) * $size . "," . $size;
//             //数据操作formatTimes D("member")->merge_exp($user_id);     //统计当前用户的经验值
//             $list = VisitorMemberService::getInstance()->getList($uid,$limit);
//             foreach($list as $key=>$value){
//                 $list[$key]['avatar'] = getavatar($value['avatar']);        //头像
//                 $list[$key]['ctime'] = formatTimes($value['ctime']);        //访问时间
//                 $list[$key]['lv_dengji'] = lv_dengji(floor($value['totalcoin']));  //等级
//                 $total_expvalue = D("member")->merge_exp($value['user_id']);
//                 $list[$key]['vip_dengji'] = vip_grade($total_expvalue['total_expvalue']);  //vip等级
// //                var_dump($total_expvalue['total_expvalue']);
//                 //根据当前用户uid,去查询对应访问user_id的消费总和,(uid向此用户user_id消费总和)
//                 $duke_coin = CoindetailService::getInstance()->getMembercoin($uid,$value['user_id']); //用户统计数据操作
//                 $list[$key]['duke_grade'] = duke_grade($duke_coin[0]['coin']);  //爵位等级

//            $where['touid'] = $uid;
//            VisitorMemberService::getInstance()->updateStatus($where);      //修改查看用户的状态
            //设置当前用户的redis缓存(清除缓存值)
            $visitKeyCount = 'visit_count';
            RedisCache::getInstance()->getRedis()->hSet($visitKeyCount,$uid,0);
            //分页page
            ParamCheck::checkInt("page",$data['page'],1);
            // 每页条数
            $size = 20;
            $start = ($data['page'] - 1) * $size;
            $end = $size*$data['page'];
            $visitKey = 'visit_user_'.$uid;         //访客id
            $visitTimeKey = 'visit_time';           //访客id的对应的时间
            $count = RedisCache::getInstance()->getRedis()->ZCARD($visitKey);      //获取该用户总访问数据量
            // 总页数.
            $totalPage = ceil($count/$size);
//            var_dump($end);die();
            // 页数信息.
            $pageInfo = array("page" => $page, "pageNum"=>$size, "totalPage" => $totalPage);
//            $redisList = RedisCache::getInstance()->getRedis()->ZREVRANGE($visitKey,$start,$end-1);
            $redisList = RedisCache::getInstance()->getRedis()->ZREVRANGE($visitKey,0,-1);
//            var_dump($redisList);die();
            if($redisList){
                $hashkey = implode(',',$redisList);
                $alltime = RedisCache::getInstance()->getRedis()->HMGET($visitTimeKey,$redisList);
                $nickname = D("member")->field('id as user_id,nickname,intro,sex,lv_dengji,avatar')->where(array('id'=>array("in",$hashkey)))->select();
                if(!empty($nickname)){
                    foreach($nickname as $namek=>$namev){
                        $nickname[$namev['user_id']] = $namev;
                    }
                }
             $list = [];
                foreach($redisList as $k=>$v){
                    if(empty($v)){
                        break;
                    }
                    $list[$k]['user_id'] = $v;          //用户id
                    $list[$k]['nickname'] = $nickname[$v]['nickname']?$nickname[$v]['nickname']:'';        //用户昵称
                    //$list[$k]['user_id'] = $nickname[$v]['avatar']?$nickname[$v]['avatar']:'';
                    $list[$k]['avatar'] = getavatar($nickname[$v]['avatar']);        //用户头像
                    $list[$k]['intro'] = $nickname[$v]['intro']?$nickname[$v]['intro']:'';        //用户简介
                    $list[$k]['sex'] = $nickname[$v]['sex'];        //用户性别
                    $list[$k]['lv_dengji'] = $nickname[$v]['lv_dengji'];        //用户等级
                    $list[$k]['ctime'] = formatTimes($alltime[$v]?$alltime[$v]:'');     //访问时间
                    $list[$k]['vip_dengji'] = "";        //用户vip等级
                    $list[$k]['duke_grade'] = "";        //用户爵位等级
                    $arrtme[] = ($alltime[$v]?$alltime[$v]:'');     //访问时间
                }

//                print_r($arrtme);die();
                array_multisort($arrtme,SORT_DESC,$list);
                $list = array_slice($list,$start,$end);
            }else {
                //             // 每页条数
                $size = 20;
                $pageNum = empty($size) ? 20 : $size;
                $page = empty($data['page']) ? 1 : $data['page'];
                $where['touid'] = $uid;
                $count = VisitorMemberService::getInstance()->countes($where);
                //修改查看用户的状态
                VisitorMemberService::getInstance()->updateStatus($where);
                //            var_dump($count);die();
                // 总页数.
                $totalPage = ceil($count / $pageNum);
                // 页数信息.
                $pageInfo = array("page" => $page, "pageNum" => $pageNum, "totalPage" => $totalPage);
                $limit = ($page - 1) * $size . "," . $size;
                //数据操作formatTimes D("member")->merge_exp($user_id);     //统计当前用户的经验值
                $list = VisitorMemberService::getInstance()->getList($uid, $limit);
                foreach ($list as $key => $value) {
                    $list[$key]['avatar'] = getavatar($value['avatar']);        //头像
                    $list[$key]['ctime'] = formatTimes($value['ctime']);        //访问时间
                    $list[$key]['lv_dengji'] = lv_dengji(floor($value['totalcoin']));  //等级
                    $list[$key]['vip_dengji'] = "";  //vip等级
                    $list[$key]['duke_grade'] = "";  //爵位等级
                }
            }
            $result = [
                "history_list" => $list,
                "pageInfo" => $pageInfo,
            ];
            $this -> returnCode = 200;
            $this -> returnData = $result;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

    }

    /**
     * 将redis里面的数据加入数据库里
     * 将redis所有的时间取出来
     */
    public function addvisitor(){
        $visitKey = 'visit_user_'.'*';         //访客id
        $visitTimeKey = 'visit_time';           //访客id的对应的时间
        $allkeys = RedisCache::getInstance()->getRedis()->keys($visitKey);      //获取该用户总访问数据量
        $endcount = count($allkeys);        //统计所有的数据
//        print_r($allkeys);
        //找出访客最后的id
        if($allkeys){
            //将取出来的键值进行分割 取出user_id
            foreach($allkeys as $key=>$value){
                $arrTmp[] = explode("_",$value);
            }
            foreach($arrTmp as $k=>$v){
                $userData[] = $v[2];
            }
        }
        $userDatalist = array_combine($userData,$allkeys);
//        print_r($userDatalist);die();
        foreach($userDatalist as $ak=>$av){
            $userDatalistes[$ak]['touid'] = $ak;
            $userDatalistes[$ak]['user_id'] = RedisCache::getInstance()->getRedis()->ZREVRANGE($av,0,$endcount);
        }
//        print_r($new_arr);die();
//        print_r($userDatalistes);die();
        foreach($userDatalistes as $k=>$v){
//            print_r($v);
            foreach($v['user_id'] as $uk=>$uv){
                $userDatalistes[$k]['user_id'][$uk]  = RedisCache::getInstance()->getRedis()->HMGET($visitTimeKey,explode(",",$uv));
            }
        }
        $arrTmp = [];
//        print_r($userDatalistes);die();
        foreach($userDatalistes as $aa=>$bb){
//            print_r($bb);
            //循环插入数据
            $history['touid'] = $bb['touid'];
            foreach($bb['user_id'] as $kitem=>$vitem){
//                print_r($vitem);
//                print_r($vitem[key($vitem)]);
//                $bb['user_id'][$kitem]['user_id'] = key($vitem);
//                $bb['user_id'][$kitem]['ctime'] = ($vitem[key($vitem)]);
//                $history['touid'] = $bb['user_id'][$kitem]['user_id'];
//                $history['ctime'] = $bb['user_id'][$kitem]['ctime'];
                $arrTmp['user_id'] = key($vitem);
                $arrTmp['ctime'] = ($vitem[key($vitem)]);
                $history['uid'] = $arrTmp['user_id'];
                $history['ctime'] = $arrTmp['ctime'];
                //如果这里有数据的话，那么去更新他的时间,返之就会增数据结构
                $is_repeat = M('visitor_member')->where(array("uid"=>$history['uid'],"touid"=>$history['touid']))->find();
                if($is_repeat){     //更新时间
                    $update['ctime'] = $history['ctime'];
                    $result = M('visitor_member')->where(array("uid"=>$history['uid'],"touid"=>$history['touid']))->save($update);
                    if($result){
                        //删除对应的缓存内容
                        $deletekey = "visit_user_".$history['touid'];
                        RedisCache::getInstance()->getRedis()->delete($deletekey);
                        RedisCache::getInstance()->getRedis()->delete($visitTimeKey);
                    }
                }else{          //新增加时间
                    $history['access_ip'] = $_SERVER['REMOTE_ADDR'];
                    $history['device'] = "";
                    $history['status'] = 1;
                    $result = D('visitor_member')->add($history);     //加入数据库
                    if($result){
                        //删除对应的缓存内容
                        $deletekey = "visit_user_".$history['touid'];
                        RedisCache::getInstance()->getRedis()->delete($deletekey);
                        RedisCache::getInstance()->getRedis()->delete($visitTimeKey);
                    }
                }

            }

        }
    }


    //更改等级跑数据
    public function updatelvdeng($token,$page){
        try{

            $size = 20;
            $pageNum = empty($size)?20:$size;
            $page = empty($page)?1:$page;
            $limit = ($page-1) * $size . "," . $size;
            // 总页数.
            $where['totalcoin'] = array('gt',0 );
            $result = M('member')->field("id,totalcoin,lv_dengji")->where($where)->limit($limit)->select();
//            var_dump($result);die();
//            echo M("member")->getLastSql();die();
            $result_count = M('member')->field("id,totalcoin,lv_dengji")->where($where)->count();
            $totalPage = ceil($result_count/$pageNum);
            // 页数信息.
            $pageInfo = array("page" => $page, "pageNum"=>$pageNum, "totalPage" => $totalPage);
            foreach($result as $key=>$value){
                $deng_chargecoin = floor(MemberService::getInstance()->getOneByIdField($value['id'],"totalcoin"));         //当前充值的虚拟币
                $lv_dengji = lv_dengji($deng_chargecoin);     //获取等级
                $result_dengji = array('lv_dengji'=>$lv_dengji);        //修改的字段值
                D('member')->updateDate($value['id'],$result_dengji);        //修改等级
            }
            $result_page = [
                "pageInfo"=>$pageInfo,
            ];
            $this -> returnCode = 200;
            $this -> returnData = $result_page;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

    }

    //将用户查出来充值数据进行修改
    public function chargecoines(){
        //统计当前用户值
        $string = "";
        $result = M('chargedetail')->field('id,uid,sum(coin) as coin')->where(array('status'=>1))->group('uid')->select();
        $result_count = M('chargedetail')->field('id,uid,sum(coin) as coin')->where(array('status'=>1))->group('uid')->count();
        for($i=0;$i<=$result_count;$i++){
            foreach($result as $key=>$value){
                $update['chargecoin'] = $value['coin'];
                $updatecoin = M("member")->where(array("id"=>$value['uid']))->save($update);
                if($updatecoin){
                    echo $i++;
                }
            }
        }
//        die();
//        $a = 0;
//        $arr = [];
//        foreach($result as $k=>$v){
//            $result[$v['uid']] = $v;
//            if($a <= 10){
//                $string .= ','.$v['uid'];
//                $a++;
//            }else{
//                $a = 0;
//                $arr[] = $string;
//                $string='';
//            }
//        }
////        var_dump($result);die();
//       // array('2,3,3,4,','5,6,6,7,8','5,6,,78m89');
//        foreach($arr as $ak=>$av){
//            $arr[$ak]['id'] = $av;          //用户id
//            $newstring = trim($av, ",");
//            $where['id'] = array('in',$newstring);
//            $resultmember = M("member")->field('id as uid,username,chargecoin')->where($where)->select();
//            echo M('member')->getLastSql();
//            foreach($resultmember as $kk=>$vv){
//                $wherees['id'] = $vv['uid'];
//                $update['chargecoin']  = $result[$vv]['coin'];
//                $member_result = M("member")->where($wherees)->save($update);
//                echo M('member')->getLastSql();die();
//            }
//
//
//        }

    }

}


?>








