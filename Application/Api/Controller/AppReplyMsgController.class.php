<?php
namespace Api\Controller;

use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Think\Log;
use Common\Util\RedisCache;
use Common\Util\emchat\Easemob;

ini_set('set_time_limit', 0);
class AppReplyMsgController extends Controller {
// class AppReplyMsgController extends BaseController {
	
	//发送app回复消息
    public function sendComment(){
    	$has = 0;
    	while (1) {
    		$has++;
	    	$redisData = RedisCache::getInstance()->getRedis()->RPOP('forum_reply_msg');
	    	if(!empty($redisData)) {
	    		// $data = array();
	    		// $data['CustomEaseMessageType'] = 2;
	    		$redisArr = json_decode($redisData,true);
		        try{              
					$Easemob = new Easemob();
					//查询评论
					$field = 'id';
					$replyRes = D('forum_reply')->getOne(array('id'=>$redisArr['reply_id'],'reply_status'=>1),$field);
					//查询贴
					$forumRes = D('forum')->getOne(array('id'=>$redisArr['forum_id'],'forum_status'=>1),'id');
					if (!empty($replyRes) && !empty($forumRes)) {

						// $content = array('msg'=>$redisArr['content']);
						// $content = json_encode($content);
						$content = $redisArr['content'];
						$fromsend = "102";
						// $ext = (object)json_encode($redisArr['msg']);
						$ext = array('XYServerData'=>$redisArr['msg']);
						$sendUid = array($redisArr['atuid']);
						$result= $Easemob->sendText($fromsend,'users',$sendUid,$content,$ext);
						if($result['error'] !== ''){
							E('消息发送失败---'.json_encode($result),2000);
						}
					}
					// else{
					// 	$data = [];
					// }

		        }catch(Exception $e){
		        	$errormsg = $e ->getCode().$e ->getMessage();
		        	$log = '/tmp/forum_replymsg_'.date('Y-m-d',time()).'.log';
		        	file_put_contents($log,"redisid : ".$redisData."----- error : ".json_encode($errormsg).PHP_EOL,FILE_APPEND);              
		        }    
		    }else{
		    	sleep(5);
		    	if ($has == 100) {
		    		exit;
		    	}
		    	// exit;
		    }
		    
	    }
	    exit;
	}





}



















