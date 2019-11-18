<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Api\Service\RedisService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\Easemob;
use Think\Exception;
use Think\Log;
/**
排行榜接口
 */
class RanklistController extends BaseController {
    private static $page_num = 20; // 默认每页显示数.18683592189 你这个APP小功能的东西比较多！东西很杂很乱！
    /*$type 榜单类型  1财富 2魅力  status 1日榜2周榜 3月榜*/
    public function rank(){
        try{
            /*初始化数据 删除过期数据*/
            $wheresss['insert_time']=date('Y-m-d',strtotime("-1 days"))."-"."00:00:00";;
             D('ranksort')->delData($wheresss);
            /*财富榜组装数组*/
            $dcoinsort=array();
            $wcoinsort=array();
            $mcoinsort=array();
            /*魅力榜组装数组*/
            $dbeansort=array();
            $wbeansort=array();
            $mbeansort=array();
            $field='id';
            $room_list = D('languageroom')->getroomlist($field);
            /*财富榜部分*/
            foreach($room_list as $k=>$v){
                /*$day=date('Y-m-d',strtotime("-1 days"))."-"."00:00:00";
                $days=date('Y-m-d',strtotime("-1 days"))."-"."23:59:59";*/
                $day=date('Y-m-d')."-"."00:00:00";
                $days=date('Y-m-d')."-"."23:59:59";
                $daycondition = array(
                    "addtime >= '".$day."' and addtime <= '".$days."'",
                    'action'=>'sendgift',
                    'room_id'=>$v['id'],
                );
                $week=date('Y-m-d',strtotime("-7 days"))."-"."00:00:00";
                $weeks=date('Y-m-d',time())."-"."00:00:00";
                $weekcondition = array(
                    "addtime >= '".$week."' and addtime <= '".$weeks."'",
                    'action'=>'sendgift',
                    'room_id'=>$v['id'],
                );
                $month=date('Y-m-d',strtotime("-1 months"))."-"."00:00:00";
                $months=date('Y-m-d',time())."-"."00:00:00";
                $monthcondition = array(
                    "addtime >= '".$month."' and addtime <= '".$months."'",
                    'action'=>'sendgift',
                    'room_id'=>$v['id'],
                );
                $coinsortd=D('coindetail')->randcoinsort($daycondition);
              //  var_dump($coinsortd);die;
                foreach($coinsortd as $k1=>$v1){
                    $where=array('room_id'=>$v['id'],'user_id'=>$v1['userid']);
                    $field='grade';
                    $dukelv=D('room_duke')->getdukelv($where,$field);
                    $fields='duke_name';
                    $dukename=D('duke')->getOneByIdField($dukelv,$fields);
                    $coinsortd[$k1]['dukename']=$dukename;
                    $totalcoin=D('member')->getByqopenid(array('id'=>$v1['userid']),'totalcoin');
                   // var_dump($totalcoin);die;
                    $user_lv=lv_dengji($totalcoin[0]['totalcoin']);//用户等级
                    $vip_lv=vip_grade($totalcoin[0]['totalcoin']);
                    $coinsortd[$k1]['user_lv']=$user_lv;
                    $coinsortd[$k1]['vip_lv']=$vip_lv; 
                    $coinsortd[$k1]['insert_time']=date('Y-m-d'); 
                    $coinsortd[$k1]['type']='1';
                    $coinsortd[$k1]['status']='1';
                    $coinsortd[$k1]['roomid']=$v['id']; 
                    
                }
               // var_dump($coinsortd);die;
                if($coinsortd !=null){
                $dcoinsort[]=$coinsortd;
                }
                $coinsortw=D('coindetail')->randcoinsort($weekcondition);
                //  var_dump($coinsortm);die;
                foreach($coinsortw as $k1=>$v1){
                    $where=array('room_id'=>$v['id'],'user_id'=>$v1['userid']);
                    $field='grade';
                    $dukelv=D('room_duke')->getdukelv($where,$field);
                    $fields='duke_name';
                    $dukename=D('duke')->getOneByIdField($dukelv,$fields);
                    $coinsortw[$k1]['dukename']=$dukename;
                    $totalcoin=D('member')->getByqopenid(array('id'=>$v1['userid']),'totalcoin');
                    // var_dump($totalcoin);die;
                    $user_lv=lv_dengji($totalcoin[0]['totalcoin']);//用户等级
                    $vip_lv=vip_grade($totalcoin[0]['totalcoin']);
                    $coinsortw[$k1]['user_lv']=$user_lv;
                    $coinsortw[$k1]['vip_lv']=$vip_lv;
                    $coinsortw[$k1]['insert_time']=date('Y-m-d');
                    $coinsortw[$k1]['type']='1';
                    $coinsortw[$k1]['status']='2';
                    $coinsortw[$k1]['roomid']=$v['id'];
                    
                }
                if($coinsortw !=null){
                    $wcoinsort[]=$coinsortw;   
                }                            
                $coinsortm=D('coindetail')->randcoinsort($monthcondition);
                //  var_dump($coinsortm);die;
                foreach($coinsortm as $k1=>$v1){
                    $where=array('room_id'=>$v['id'],'user_id'=>$v1['userid']);
                    $field='grade';
                    $dukelv=D('room_duke')->getdukelv($where,$field);
                    $fields='duke_name';
                    $dukename=D('duke')->getOneByIdField($dukelv,$fields);
                    $coinsortm[$k1]['dukename']=$dukename;
                    $totalcoin=D('member')->getByqopenid(array('id'=>$v1['userid']),'totalcoin');
                    // var_dump($totalcoin);die;
                    $user_lv=lv_dengji($totalcoin[0]['totalcoin']);//用户等级
                    $vip_lv=vip_grade($totalcoin[0]['totalcoin']);
                    $coinsortm[$k1]['user_lv']=$user_lv;
                    $coinsortm[$k1]['vip_lv']=$vip_lv;
                    $coinsortm[$k1]['insert_time']=date('Y-m-d');
                    $coinsortm[$k1]['type']='1';
                    $coinsortm[$k1]['status']='3';
                    $coinsortm[$k1]['roomid']=$v['id'];
                    
                }
                if($coinsortm !=null){
                    $mcoinsort[]=$coinsortm;
                }
            }

         //每天凌晨三点更新执行插入数据库操作
        // var_dump($dcoinsort);die;
        // var_dump($wcoinsort);die;
            foreach($dcoinsort as $k=>$v){
                foreach($v as $k1=>$v1){
                    D('ranksort')->addData($v1);
                }

            }
            foreach($wcoinsort as $k=>$v){
                foreach($v as $k1=>$v1){
                    D('ranksort')->addData($v1);
                }
            }
            foreach($mcoinsort as $k=>$v){
                foreach($v as $k1=>$v1){
                    D('ranksort')->addData($v1);
                }
            }
            /*魅力榜部分*/
            foreach($room_list as $k=>$v){
                $day=date('Y-m-d',strtotime("-1 days"))."-"."00:00:00";
                $days=date('Y-m-d',strtotime("-1 days"))."-"."23:59:59";
                $daycondition = array(
                    "addtime >= '".$day."' and addtime <= '".$days."'",
                    'action'=>'get_gift ',
                    'room_id'=>$v['id'],
                );
                $week=date('Y-m-d',strtotime("-7 days"))."-"."00:00:00";
                $weeks=date('Y-m-d',time())."-"."00:00:00";
                $weekcondition = array(
                    "addtime >= '".$week."' and addtime <= '".$weeks."'",
                    'action'=>'get_gift ',
                    'room_id'=>$v['id'],
                );
                $month=date('Y-m-d',strtotime("-1 months"))."-"."00:00:00";
                $months=date('Y-m-d',time())."-"."00:00:00";
                $monthcondition = array(
                    "addtime >= '".$month."' and addtime <= '".$months."'",
                    'action'=>'get_gift ',
                    'room_id'=>$v['id'],
                );
                $beansortd=D('beandetail')->randbeansort($daycondition);
                foreach($beansortd as $k1=>$v1){
                    $where=array('room_id'=>$v['id'],'user_id'=>$v1['userid']);
                    $field='grade';
                    $dukelv=D('room_duke')->getdukelv($where,$field);
                    $fields='duke_name';
                    $dukename=D('duke')->getOneByIdField($dukelv,$fields);
                    $beansortd[$k1]['dukename']=$dukename;
                    $totalcoin=D('member')->getByqopenid(array('id'=>$v1['userid']),'totalcoin');
                    // var_dump($totalcoin);die;
                    $user_lv=lv_dengji($totalcoin[0]['totalcoin']);//用户等级
                    $vip_lv=vip_grade($totalcoin[0]['totalcoin']);
                    $beansortd[$k1]['user_lv']=$user_lv;
                    $beansortd[$k1]['vip_lv']=$vip_lv;
                    $beansortd[$k1]['insert_time']=date('Y-m-d');
                    $beansortd[$k1]['type']='2';
                    $beansortd[$k1]['status']='1';
                    $beansortd[$k1]['roomid']=$v['id'];
                    
                }
                // var_dump($coinsortd);die;
                if($beansortd !=null){
                    $dbeansort[]=$beansortd;
                }
                $beansortw=D('beandetail')->randbeansort($weekcondition);
                //  var_dump($coinsortm);die;
                foreach($beansortw as $k1=>$v1){
                    $where=array('room_id'=>$v['id'],'user_id'=>$v1['userid']);
                    $field='grade';
                    $dukelv=D('room_duke')->getdukelv($where,$field);
                    $fields='duke_name';
                    $dukename=D('duke')->getOneByIdField($dukelv,$fields);
                    $beansortw[$k1]['dukename']=$dukename;
                    $totalcoin=D('member')->getByqopenid(array('id'=>$v1['userid']),'totalcoin');
                    // var_dump($totalcoin);die;
                    $user_lv=lv_dengji($totalcoin[0]['totalcoin']);//用户等级
                    $vip_lv=vip_grade($totalcoin[0]['totalcoin']);
                    $beansortw[$k1]['user_lv']=$user_lv;
                    $beansortw[$k1]['vip_lv']=$vip_lv;
                    $beansortw[$k1]['insert_time']=date('Y-m-d');
                    $beansortw[$k1]['type']='2';
                    $beansortw[$k1]['status']='2';
                    $beansortw[$k1]['roomid']=$v['id'];
                    
                }
                if($beansortw !=null){
                    $wbeansort[]=$beansortw;
                }
                $beansortm=D('beandetail')->randbeansort($monthcondition);
                foreach($beansortm as $k1=>$v1){
                    $where=array('room_id'=>$v['id'],'user_id'=>$v1['userid']);
                    $field='grade';
                    $dukelv=D('room_duke')->getdukelv($where,$field);
                    $fields='duke_name';
                    $dukename=D('duke')->getOneByIdField($dukelv,$fields);
                    $beansortm[$k1]['dukename']=$dukename;
                    $totalcoin=D('member')->getByqopenid(array('id'=>$v1['userid']),'totalcoin');
                    // var_dump($totalcoin);die;
                    $user_lv=lv_dengji($totalcoin[0]['totalcoin']);//用户等级
                    $vip_lv=vip_grade($totalcoin[0]['totalcoin']);
                    $beansortm[$k1]['user_lv']=$user_lv;
                    $beansortm[$k1]['vip_lv']=$vip_lv;
                    $beansortm[$k1]['insert_time']=date('Y-m-d');
                    $beansortm[$k1]['type']='2';
                    $beansortm[$k1]['status']='3';
                    $beansortm[$k1]['roomid']=$v['id'];
                    
                }
                if($beansortm !=null){
                    $mbeansort[]=$beansortm;
                }
            }
            
            //每天凌晨三点更新执行插入数据库操作
            // var_dump($dcoinsort);die;
            // var_dump($wcoinsort);die;
            foreach($beansortd as $k=>$v){
                foreach($v as $k1=>$v1){
                    D('ranksort')->addData($v1);
                }
                
            }
            foreach($wbeansort as $k=>$v){
                foreach($v as $k1=>$v1){
                    D('ranksort')->addData($v1);
                }
            }
            foreach($mbeansort as $k=>$v){
                foreach($v as $k1=>$v1){
                    D('ranksort')->addData($v1);
                }
            }
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
        $this -> returnData();
    }

    // private function redisConn()
    // {
    //    $redis = new Redis(); 
    //    $redis->connect('127.0.0.1', 6379); 
    //    $redis->auth('Etang123'); 
    //    $redis->select(2);
    //    $redis->set( "testKey" , "Hello Redis"); //设置测试key
    //    echo $redis->get("testKey");//输出value
    // }
    
    /*排行榜列表接口*/
    public function ranklist($token,$type,$status,$roomid=null){
        $redisKey = '';
        $blackUser = array();

        //判断type
         if ($type == 1) {
            $redisKey .= 'Rich';
            $blackUser = array(1015799,1011487,1047533,1037331,1000001,1000009,1037719,1028651,1000042,1000095,1020261,1012582,1020596,1017832,1000186,1001203,1016768,1000227,1012089,1000130,1000008,1000072,1000023,1000048,1000344,1000345,1000028,1004280,1000183,1002820,1005305,1001197,1004669,1010842,1000043,1037284,1000042
);
        }else{
            $redisKey .= 'Like';
            $blackUser = array(1005608,1000001,1000009,1028651,1000042,1000095,1020261,1012582,1000009,1000006,1000005,1005860);
        }

        //判断$status
        switch ($status) {
            case 1:
                $redisKey .= '_Day_0_'.date("Ymd",time());
                break;
            case 2:
                // $redisKey .= '_Week_0_'.date("Ymd",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")));
                $redisKey .= '_Week_0_'.date('Ymd',(time()-((date('w',time())==0?7:date('w',time()))-1)*24*3600));
                break;
            case 3:
                $redisKey .= '_Month_0_'.date("Ym",mktime(00,00,00,date("m"),date("t"),date("Y")));
                break;
            
            default:
                $this -> returnCode = 500;
                $this -> returnMsg = "操作失败";
                $this -> returnData=[];
                break;
        }

        $result = array();
        $redisconn = new \Redis();
        $redisHost = C('REDIS_HOST');
        $redisconn->connect($redisHost, 6379);
        $redisconn->auth('Etang123');
        $redisconn->select(C('REDISDB'));
        $rank = $redisconn->ZREVRANGE($redisKey, 0, 100, 'WITHSCORES');
        Log::record("ranklist---------：".json_encode($rank), "INFO" );
        Log::record("redisKey ranklist---------：".$redisKey, "INFO" );
        if (empty($rank)) {
            $this -> returnCode = 200;
            $this -> returnMsg = "操作成功";
            $this -> returnData=array();
        }
        $rankTmp = array();
        foreach ($rank as $key => $value) {
            if (!in_array($key, $blackUser)) {
                $rankTmp[$key] = $value;
            }
        }
        $rankUser = array_slice($rankTmp, 0, 50, true);
        $rankUserId = array_keys($rankUser);
        $uidStr = implode(',', $rankUserId);
        $field='id,nickname,avatar,sex,lv_dengji';
        $userList=D('member')->getOneByIdsField($uidStr,$field);

        if (!empty($userList)) {
            foreach ($rankTmp as $key => $value) {
                if ($key == $userList[$key]['id']) {
                    $result[] = $userList[$key];
                }
            }
            foreach ($result as $key => $value) {
                $result[$key]['coin'] = $rankTmp[$value['id']];
                $result[$key]['userid'] = $value['id'];
                $result[$key]['avatar'] = C('APP_URL').$value['avatar'];

                //老版本字段
                $result[$key]['rankid'] = 1;
                $result[$key]['roomid'] = 1;
                $result[$key]['dukename'] = 'none';
                $result[$key]['user_lv'] = $value['lv_dengji'];
                $result[$key]['vip_lv'] = 1;
            }
            //var_dump($result);
            $this -> returnCode = 200;
            $this -> returnMsg = "操作成功";
            $this -> returnData=$result;
            $this -> returnData();
        }
        $this -> returnCode = 200;
        $this -> returnMsg = "操作成功";
        $this -> returnData=array();
        $this -> returnData();
        exit;
        

        // var_dump($rank);exit;
        try{
        $day=date('Y-m-d')."-"."00:00:00";
//      $where=array('type'=>$type,'status'=>$status,'roomid'=>$roomid,'insert_time'=>$day);
        $where=array('type'=>$type,'status'=>$status,'insert_time'=>$day);
        $field='id as rankid,userid,roomid,nickname,avatar,sex,dukename,user_lv,vip_lv,coin';
        $ranklistss=D('ranksort')->rankList($where,$field,'20');
        $ranklist=array();
            foreach($ranklistss as $k=>$v){
                $ranklists=dealnull($v);
                $ranklist[]=$ranklists;
            }
            $this -> returnCode = 200;
            $this -> returnMsg = "操作成功";
            $this -> returnData=$ranklist;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }

    /**排行榜列表(全用户的前20名)
     * @param $token    token值
     * @param $type     type类型 1
     * @param $status   1日d,2周w,3月m
     */
    public function ranklistes($token,$type,$status){
        try{
            $day=date('Y-m-d')."-"."00:00:00";
            $where=array('type'=>$type,'status'=>$status,'insert_time'=>$day);
            $field='id as rankid,userid,roomid,nickname,avatar,sex,dukename,user_lv,vip_lv,coin';
            $ranklistss=D('ranksort')->rankListes($where,$field,'20');
            $ranklist=array();
            foreach($ranklistss as $k=>$v){
                $ranklists=dealnull($v);
                $ranklist[]=$ranklists;
            }
            $this -> returnCode = 200;
            $this -> returnMsg = "操作成功";
            $this -> returnData=$ranklist;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();

        }
        $this -> returnData();
    }

    /**首页财富榜与心动值榜的前三名头像
     * @param $token    token值
     */
    public function indexrank($token){
        //获取token
        $data = [
            "token" => I('get.token'),
        ];
        $redisKeyrich = '';
        $redisKeylike = '';
        try{
            //redis的键值对象
            $redisKeyrich .= 'Rich';    //财富榜
            $redisKeyrich .= '_Day_0_'.date("Ymd",time());      //Rich_Day_0_20190821
            $redisKeylike .= 'Like';        //心动值
            $redisKeylike .= '_Day_0_'.date("Ymd",time());
//            $redisKeyrich = "Rich_Day_0_20190821";
//            $redisKeylike = "Like_Day_0_20190821";
            //redis的数据
            $redisconn = new \Redis();
            $redisHost = C('REDIS_HOST');
            $redisconn->connect($redisHost, C('REDIS_PORT'));
            $redisconn->auth(C('REDIS_PWD'));
            $redisconn->select(C('REDISDB'));
            //财富榜数据(及排队数据)
            $blackUser = array(1015799,1037331,1028651,1000042,1000095,1020261,1012582,1020596,1017832,1000186,1001203,1016768,1000227,1012089,1000130,1000008,1000072,1000023,1000048,1000344,1000345,1000028,1004280,1000183,1002820,1005305,1001197,1004669,1010842,1000043,1037284,1000042
            );
//            $rank_rich = $redisconn->ZREVRANGE($redisKeyrich, 0, 2, 'WITHSCORES');          //财富榜
            $rank_rich = $redisconn->ZREVRANGE($redisKeyrich, 0, 50, 'WITHSCORES');          //财富榜
//            print_r($rank_rich);die();
            $rankTmp = array();
            foreach ($rank_rich as $key => $value) {
//                $rankTmp[$key] = $value;
                if (!in_array($key, $blackUser)) {
                    $rankTmp[$key] = $value;
                }
            }
            $rankUser = array_slice($rankTmp, 0, 3, true);
            $rankUserId = array_keys($rankUser);
//            print_r($rankUserId);die();
            $uidStr = implode(',', $rankUserId);
            $field='id,nickname,avatar,sex';
            $userList=D('member')->getOneByIdsField($uidStr,$field);
            if($userList){
                foreach($userList as $key=>$value){
                    $userList[$key]['avatar'] = getavatar($value['avatar']);
                }
                //根据每条记录的字段id，去$b中查找对应的键值，作为这一条记录的键值。
                $newarr_rich = [];
                foreach ($userList as $v) {
                    $k = array_search($v['id'],$rankUserId);
                    $newarr_rich[$k] = $v;
                }
                ksort($newarr_rich);
                $newarr_rich = array_values($newarr_rich);
                unset($newarr_rich[0]['id']);
                unset($newarr_rich[1]['id']);
                unset($newarr_rich[2]['id']);
//                var_dump($newarr_rich[$k]['id']);die();
            }else{
                $newarr_rich = [];
            }
            //心动值数据
            $blackUser = array(1005608,1028651,1000042,1000095,1020261,1012582,1000009,1000006,1000005,1005860);
//            $rank_like = $redisconn->ZREVRANGE($redisKeylike, 0, 2, 'WITHSCORES');          //心动榜
            $rank_like = $redisconn->ZREVRANGE($redisKeylike, 0, 50, 'WITHSCORES');          //心动榜
            $rankTmpLike = array();
            foreach ($rank_like as $key => $value) {
//                $rankTmpLike[$key] = $value;
                if (!in_array($key, $blackUser)) {
                    $rankTmpLike[$key] = $value;
                }
            }
            $rankUser = array_slice($rankTmpLike, 0, 3, true);
            $rankUserIds = array_keys($rankUser);
            $uidStrs = implode(',', $rankUserIds);
            $field='id,nickname,avatar,sex';
            $userLists=D('member')->getOneByIdsField($uidStrs,$field);
            if($userLists){
                foreach($userLists as $key=>$value){
                    $userLists[$key]['avatar'] = getavatar($value['avatar']);
                }
                //根据每条记录的字段id，去$b中查找对应的键值，作为这一条记录的键值。
                $newarr_like = [];
                foreach ($userLists as $v) {
                    $k = array_search($v['id'],$rankUserIds);
                    $newarr_like[$k] = $v;
                }
                ksort($newarr_like);
                $newarr_like = array_values($newarr_like);
                unset($newarr_like[0]['id']);
                unset($newarr_like[1]['id']);
                unset($newarr_like[2]['id']);
            }else{
                $newarr_like = [];
            }
            $result = [
                "rich_list" => $newarr_rich,
                "like_list" => $newarr_like,
            ];
            $this -> returnCode = 200;
            $this -> returnData = $result;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();

        }
        $this -> returnData();
    }
        

}
