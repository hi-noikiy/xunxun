<?php
namespace Api\Controller;

use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Think\Log;
use Common\Util\RedisCache;
use Common\Util\emchat\Easemob;

ini_set('set_time_limit', 0);
class AppreplyController extends BaseController {
	
	//发送app回复消息
    public function sendReplyMsg(){
    	while (1) {
	    	$redisData = RedisCache::getInstance()->getRedis()->RPOP('forum_reply_msg');
	    	if(!empty($redisData)) {
	    		$data = array();
	    		$data['CustomEaseMessageType'] = 2;
		        try{              
					$Easemob = new Easemob();
					//查询评论
					$field = 'id as reply_id,reply_content,reply_uid,reply_atuid,createtime,reply_type,forum_id,reply_parent_id';
					$replyRes = D('forum_reply')->getOne(array('id'=>$redisData,'reply_status'=>1),$field);
					//查询贴
					$forumRes = D('forum')->getOne(array('id'=>$replyRes['forum_id'],'forum_status'=>1),'id as forum_id,forum_uid,forum_content,forum_image,forum_voice,createtime,forum_voice_time');
					$uids = $replyRes['reply_uid'].','.$replyRes['reply_atuid'].','.$forumRes['forum_uid'];
					$userRes = D('member')->getlistAllByWhere(array('id'=>array('in',$uids)),'id as uid,nickname,avatar,sex');
					if (!empty($replyRes) && !empty($forumRes)) {
						if ($replyRes['reply_type'] == 1) {//回帖
							$data['forum']['forum_id'] = $forumRes['forum_id'];
			                $data['forum']['forum_uid'] = $forumRes['forum_uid'];
			                $data['forum']['avatar'] = $userRes[$forumRes['forum_uid']]['avatar']?C('APP_URL_image').'/'.$userRes[$forumRes['forum_uid']]['avatar']:'';
			                $data['forum']['nickname'] = $userRes[$forumRes['forum_uid']]['nickname']?$userRes[$forumRes['forum_uid']]['nickname']:'';
			                $data['forum']['forum_content'] = $forumRes['forum_content']?$forumRes['forum_content']:'';
			                $arr = explode(',', $forumRes['forum_image']);
				            foreach ($arr as $key => &$value) {
				            	$value = $value?C('APP_URL_image').'/'.$value:'';
				            }
			                $data['forum']['forum_image'] = implode(',', $arr);
			                $data['forum']['forum_voice'] = $forumRes['forum_voice']?C('APP_URL_image').'/'.$forumRes['forum_voice']:'';
			                $data['forum']['sex'] = $userRes[$forumRes['forum_uid']]['sex'];
			                $data['forum']['createtime'] = date('Y-m-d H:i:s',$forumRes['createtime']);
			                $data['forum']['reply_num'] = D('forum_reply')->countNum(array('forum_id'=>$forumRes['forum_id'],'reply_status'=>1));
						}else{
							$data['reply']['reply_id'] = $replyRes['reply_id'];
			                $data['reply']['reply_content'] = $replyRes['reply_content'];
			                $data['reply']['createtime'] = date('Y-m-d H:i:s',$replyRes['createtime']);
			                $data['reply']['reply_num'] = D('forum_reply')->countNum(array('forum_id'=>$forumRes['forum_id'],'reply_status'=>1,'reply_parent_id'=>$replyRes['reply_parent_id']));
						}
						$data['addReply']['reply_id'] = $replyRes['reply_id'];
						$data['addReply']['reply_uid'] = $replyRes['reply_uid'];
						$data['addReply']['reply_uid_avatar'] = $userRes[$replyRes['reply_uid']]['avatar']?C('APP_URL_image').'/'.$userRes[$replyRes['reply_uid']]['avatar']:'';
						$data['addReply']['reply_uid_nickname'] = $userRes[$replyRes['reply_uid']]['nickname'];
						$data['addReply']['reply_uid_sex'] = $userRes[$replyRes['reply_uid']]['sex'];
						$data['addReply']['reply_content'] = $replyRes['reply_content'];

						$data['addReply']['reply_atuid'] = $replyRes['reply_atuid'];
						$data['addReply']['reply_atuid_avatar'] = $userRes[$replyRes['reply_atuid']]['avatar']?C('APP_URL_image').'/'.$userRes[$replyRes['reply_atuid']]['avatar']:'';
						$data['addReply']['reply_atuid_atnickname'] = $userRes[$replyRes['reply_atuid']]['nickname'];

						$content = array('msg'=>$replyRes['reply_content']);
						$content = json_encode($content);
						$fromsend = "Mua";
						$ext = (object)json_encode($data);
						$sendUid = array($replyRes['reply_atuid']);
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
		        	$log = '/tmp/forum_'.date('Y-m-d',time()).'.log';
		        	file_put_contents($log,"redisid : ".$redisData."----- error : ".json_encode($errormsg).PHP_EOL,FILE_APPEND);              
		        }    
		    }else{
		    	sleep(3);
		    	exit;
		    }
		    
	    }
	    exit;
	}





}



















