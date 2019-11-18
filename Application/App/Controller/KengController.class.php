<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\Easemob;
use Think\Exception;
use Think\Log;

class KengController extends BaseController
{
    
    //上麦接口
    public function upmaixu($roomid,$maixu=null,$token,$type=null){
        try{
             $roomid=$_REQUEST['roomid'];
            $maixu=$_REQUEST['maixu'];         
            $userid = RedisCache::getInstance()->get($token);
            //var_dump($userid);die;
            $type=$_REQUEST['type'];
            if($type==1){
            $file="updown/".$roomid.'.txt';            
            /*$array=array(
                        array("0"=>null,"maidong"=>"0"),
                        array("1"=>null,"maidong"=>"0"),
                        array("2"=>null,"maidong"=>"0"),
                        array("3"=>null,"maidong"=>"0"),
                        array("4"=>null,"maidong"=>"0"),
                        array("5"=>null,"maidong"=>"0"),
                        array("6"=>null,"maidong"=>"0"),
                        array("7"=>null,"maidong"=>"0"),
                        array("8"=>null,"maidong"=>"0")
                        );//存储麦序*/
                $array=array(
                    array("0"=>null,"maidong"=>"0","starttime"=>"0","totalseconds"=>"0"),
                    array("1"=>null,"maidong"=>"0","starttime"=>"0","totalseconds"=>"0"),
                    array("2"=>null,"maidong"=>"0","starttime"=>"0","totalseconds"=>"0"),
                    array("3"=>null,"maidong"=>"0","starttime"=>"0","totalseconds"=>"0"),
                    array("4"=>null,"maidong"=>"0","starttime"=>"0","totalseconds"=>"0"),
                    array("5"=>null,"maidong"=>"0","starttime"=>"0","totalseconds"=>"0"),
                    array("6"=>null,"maidong"=>"0","starttime"=>"0","totalseconds"=>"0"),
                    array("7"=>null,"maidong"=>"0","starttime"=>"0","totalseconds"=>"0"),
                    array("8"=>null,"maidong"=>"0","starttime"=>"0","totalseconds"=>"0")
                );//存储麦序
            if(!file_exists($file)){
                if(false!==fopen($file,'w+')){
                    file_put_contents($file,serialize($array));//写入缓存
                } 
                $handle=fopen($file,'r');
                $cacheArray=unserialize(fread($handle,filesize($file)));
                $this -> returnCode = 200;
                $this -> returnData=$cacheArray;
            }else{
                $field='id as uid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';                
                $user_info = D('member')->getOneByIdField($userid,$field);
                ///var_dump($user_info[$userid]["avatar"]);die;
                if($user_info[$userid]["avatar"]==null){
                    
                }else{
                    $user_info[$userid]["avatar"]=C("APP_URL"). $user_info[$userid]["avatar"];
                }
               // var_dump($user_info);die;
                $handle=fopen($file,'r');
                $cacheArray=unserialize(fread($handle,filesize($file)));
               // var_dump($cacheArray[$maixu][$maixu]);die;
               //判断麦上是否有人0有人 1 无人
                if($cacheArray[$maixu][$maixu]==null){
                    $this -> returnCode = 200;
                   // $this -> returnData="";
                }else{
                    E('麦上有人',2000);
                }
                
                $cacheArray[$maixu][$maixu]=$user_info[$userid];
                //$a=array_values($cacheArray);
               // var_dump($a);die;
                //缓存
                file_put_contents($file,serialize($cacheArray));//写入缓存
                ///var_dump($cacheArray);die;
               
               
            }
            }elseif($type=="2"){
                $field='id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';
                $user_info = D('member')->getOneByIdField($userid,$field);               
                $arList =RedisCache::getInstance()->getList("maixusort".$roomid,0,19);  
               // RedisCache::getInstance()->lrem("maixusort".$roomid,$userid."-".$maixu);
                // RedisCache::getInstance()->rmList("maixusort".$roomid);
                    // var_dump($arList);die;          
                if(count($arList)==20){
                    E("麦序已经排满，请稍后再试",2001);
                }
                foreach($arList as $k=>$v){
                    // var_dump($v);die;
                    if($v==$userid."-".$maixu){
                        E('您已经在排麦中',2002);
                    }
                }  
                RedisCache::getInstance()->setList("maixusort".$roomid,$userid."-".$maixu);
             
            }
            //读出缓存         
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
            $this -> returnData();
    }  

    //下麦接口
    public function downmaixu($roomid,$maixu=null,$userid=null,$type=null){
        try{
            $file="updown/".$roomid.'.txt';
            //var_dump($file);die;
            //$array=array("maixu1"=>null,"maixu2"=>null,"maixu3"=>null,"maixu4"=>null,"maixu5"=>null,"maixu6"=>null,"maixu7"=>null,"maixu8"=>null);//存储麦序
                $handle=fopen($file,'r');
                $cacheArray=unserialize(fread($handle,filesize($file)));
           
                $cacheArray[$maixu][$maixu]=null;
                  file_put_contents($file,serialize($cacheArray));
                    // var_dump($cacheArray);die;
                $this -> returnCode = 200;
                $this -> returnData=$cacheArray;

            //读出缓存
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
        $this -> returnData();
    } 
    
        //麦序列表接口
    public function getsortmaixulist($roomid,$maixu=null,$userid=null,$type=null){
            $roomid=$_REQUEST['roomid'];
        try{
           $arList =RedisCache::getInstance()->getList("maixusort".$roomid,0,19);
             $user_info=array();
           foreach($arList as $k=>$v){
               $userid=explode("-",$v);
               $field='id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';
               $user_infos = D('member')->getOneByIdField($userid[0],$field);
               $user_infos[$userid[0]]['maixu']=$userid[1];
              // var_dump($user_infos);die;
               $user_info[]=dealnull($user_infos[$userid[0]]);
           }
         // var_dump($user_info);die;
           $result = [
               "info"=>$user_info,
           ];
           $this -> returnCode = 200;
           $this -> returnMsg = "操作成功";
           $this -> returnData=$result;  
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
        $this -> returnData();
    }
}
