<?php
namespace Api\Controller;

use Api\Service\MemberService;
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
    
    /*排行榜列表接口*/
    public function ranklist($token,$type,$status,$roomid=null){
        try{
        $day=date('Y-m-d')."-"."00:00:00";
//        $where=array('type'=>$type,'status'=>$status,'roomid'=>$roomid,'insert_time'=>$day);
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

    
        

}