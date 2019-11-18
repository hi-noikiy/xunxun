<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\emchat\Easemob;
use Think\Exception;
use Think\Log;
 class PushmessageController extends BaseController{
     /**发送系统消息
	function sendText($from="admin",$target_type,$target,$content,$ext){
		$url=$this->url.'messages';
		$body['target_type']=$target_type;
		$body['target']=$target;
		$options['type']="txt";
		$options['msg']=$content;
		$body['msg']=$options;
		$body['from']=$from;
		$body['ext']=$ext;
		$b=json_encode($body);
		$header=array($this->getToken());
		$result=$this->postCurl($url,$b,$header);
		return $result;
	}
      * 
      * **/
     public function sendAll($content){
         try{
             $Easemob=new Easemob();
             $users=$Easemob->getUsers();
             $username=array();
             foreach($users['entities'] as $k=>$v){
                 $username[]=$v['username'];
             }
             $fromsend="Mua";
             $result= $Easemob->sendText($fromsend,'users',$username,$content);
             if($result['error']==""){
//                  $data=array('fromsend'=>$fromsend,'pushmsg'=>$content,'pushtime'=>date('Y-m-d H:i:s',time()));
//                  D('pushmsg')->addData($data); 
             }else{
                 E('消息发送失败',2000);
             }
             $this -> returnCode = 200;
             $this -> returnMsg = "操作成功";
         }catch(Exception $e){
             $this -> returnCode = $e ->getCode();
             $this -> returnMsg = $e ->getMessage();
             
         }
             $this -> returnData();
     }
     
         /**粉丝消息提*/	
         public function sendfans($token){
             try{
                 $userid = RedisCache::getInstance()->get($token);                 
                 $Easemob=new Easemob();
                 $nickname=D('member')->getOneByIdField($userid,'nickname');  
                 $room_id=D('languageroom')->getOneByIdFields(array('user_id'=>$userid),'id');
              //   var_dump($nickname);die;     
                 //根据userid 获取粉丝用户
                 $where=array('userided'=>$userid);
                 $field='userid';
                 $fansid=D('attention')->getlist($where,$field);
                 $fansidarray=array();
                 foreach($fansid as $k=>$v){
                        //查询粉丝用户的开关状态
                        $status=D('member')->getOneByIdField($v['userid'],'fansmenustatus');
                        if($status==0){
                               $fansidarray[]=$v['userid']; 
                        }
                 }
                 $content=array('type'=>'2','msg'=>'您关注的'.$nickname."开播啦");
                 $content=json_encode($content);
                 $fromsend="Mua";
                 $result= $Easemob->sendCmd($fromsend,'users',$fansidarray,$content);
              //   var_dump($result);die;
                 if($result['error']==""){
                 }else{
                     E('消息发送失败',2000);
                 }
                 $result=[
                   'room_id'=>$room_id,
                 ];
                 $this -> returnCode = 200;
                 $this -> returnMsg = "操作成功";
                  $this -> returnData = $result;
             }catch(Exception $e){
                 $this -> returnCode = $e ->getCode();
                 $this -> returnMsg = $e ->getMessage();                 
             }    
                 $this -> returnData();
         }
         
                  /*一对一赠送礼物余额计算 发送消息*/
         public function sendgiftoneToone($token,$touid,$num="1",$giftid){
             try{
                 $time=date('Y-m-d H:i:s',time());
                 $userid = RedisCache::getInstance()->get($token);
              //   var_dump($userid);die;
                 $field='nickname,totalcoin,freecoin,diamond,free_diamond,nickname';
                 $send_userinfo=D('member')->getByqopenid(array('id'=>$userid),$field);
                 //var_dump($send_userinfo);die;
                 $get_userinfo=D('member')->getByqopenid(array('id'=>$touid),$field);
                 $giftinfo=D('gift')->findOneById($giftid);
                 $proportion=D('siteconfig')->getOneByIdField('proportion');
                 $coin_before=$send_userinfo[0]['totalcoin']-$send_userinfo[0]['freecoin'];
                 $coin_after=$coin_before-$giftinfo['gift_coin']*$num;
                 $coin=$giftinfo['gift_coin']*$num;
                 $bean=$coin*$proportion;
                 $bean_before=$get_userinfo[0]['diamond']-$get_userinfo[0]['free_diamond'];
                 $bean_after=$bean_before-$bean;
                 //var_dump($coin_before);die;
                 //组装数据存入数据表
                 $coindetail=array(
                     'action'=>'onesendgift',
                     'room_id'=>0,
                     'uid'=>$userid,
                     'touid'=>$touid,
                     'giftid'=>$giftid,
                     'giftcount'=>$num,
                     'content'=>'打赏礼物',
                     'coin'=>$coin,
                     'coin_before'=>$coin_before,
                     'coin_after'=>$coin_after,
                     'addtime'=>$time,                                                            
                 );
                // var_dump($coindetail);die;
                 $coindetail=D('coindetail')->addData($coindetail);
                 $beandetail=array(
                     'action'=>'onegetgift',
                     'uid'=>$touid,
                     'room_id'=>0,
                     'get_uid'=>$userid,
                     'content'=>'收到礼物',
                     'bean'=>$bean,
                     'bean_before'=>$bean_before,
                     'bean_after'=>$bean_after,
                     'addtime'=>$time,
                 );
                 $beandetail=D('beandetail')->addDatas($beandetail);
                 if($coindetail && $beandetail){
                     $increcoin= D('member')->where(array("id"=>$userid))->setInc('freecoin',$coin);
                     $increbean= D('member')->where(array("id"=>$touid))->setInc('diamond',$bean);
                     if($increcoin && $increbean){
                         //var_dump($user_info);die;
                         $Easemob=new Easemob();
                         $fromsend="Mua";
                        $content=array('type'=>'3','msg'=>'您收到来自'.$send_userinfo[0]['nickname']."礼物");
                         $content=json_encode($content);
                         $results= $Easemob->sendCmd($fromsend,'users',array($touid),$content);
                        // var_dump($results);die;
                         //var_dump($result);die;
                         if($results['error']==""){
                         $this -> returnCode = 200;
                         $this -> returnMsg = "操作成功";
                         }else{
                             E('消息发送失败',2000);
                         }
                     }
                     
                 }

                 $this -> returnData=$result;
             }catch(Exception $e){
                 $this -> returnCode = $e ->getCode();
                 $this -> returnMsg = $e ->getMessage();
             }
             $this -> returnData();

         }
}