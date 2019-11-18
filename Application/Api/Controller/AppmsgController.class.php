<?php
namespace Api\Controller;

use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Think\Log;
use Common\Util\RedisCache;
use Common\Util\emchat\Easemob;

ini_set('set_time_limit', 0);
class AppmsgController extends BaseController {
	
	//发送app消息
    public function sendForumMsg(){
    	while (1) {
	    	$redisData = RedisCache::getInstance()->getRedis()->RPOP('forum_msg');
	    	if(!empty($redisData)) {
		        try{              
					$Easemob = new Easemob();

					$res = explode('_', $redisData);
					$nickname = D('member')->getOneByIdField($res[0],'nickname'); 
					//查询关注我的用户
					$where = array('userided'=>$res[0]);
					$field = 'userid';
					$fansid = D('attention')->getlist($where,$field);
					if (!empty($fansid)) {
						$arrIds = array();
						foreach ($fansid as $key => $value) {
							$arrIds[] = $value['userid'];
						}
						//20用户个一组
						$teamIds = array_chunk($arrIds,20);
						for ($i=0; $i < count($teamIds); $i++) { 
							$content = array('msg'=>'您关注的'.$nickname."发表了新的动态");
							$content = json_encode($content);
							$fromsend = "Mua";
							$extObj = array('type'=>1);
							$ext = (object)json_encode($extObj);
							$result= $Easemob->sendText($fromsend,'users',$teamIds[$i],$content,$ext);
							if($result['error'] !== ''){
								E('消息发送失败---'.json_encode($result),2000);
							}
							usleep(300);
						}
					}
		        }catch(Exception $e){
		        	$errormsg = $e ->getCode().$e ->getMessage();
		        	$log = '/tmp/forum_'.date('Y-m-d',time()).'.log';
		        	file_put_contents($log,"error : ".json_encode($errormsg).PHP_EOL,FILE_APPEND);              
		        }    
		    }
		    exit;
	    }
	}





}



















