<?php
namespace Api\Controller;

include_once 'aliyun-php-sdk-core/Config.php';
use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Think\Log;
use Common\Util\RedisCache;
use Green\Request\V20180509 as Green;
use Green\Request\Extension\ClientUploader;
use DefaultProfile;
use DefaultAcsClient;

ini_set('set_time_limit', 0);
class AliOssController extends Controller {
	
	//图片检测
    public function examineOss(){
    	// $url = 'http://muatest.oss-cn-zhangjiakou.aliyuncs.com/';
    	$url = C('ALIYUNURL');
    	$stsConf = C('STSCONF');
    	$accessKeyID = $stsConf['AccessKeyID'];
	    $accessKeySecret = $stsConf['AccessKeySecret'];
	    $roleArn = $stsConf['RoleArn'];
	    $tokenExpire = $stsConf['TokenExpireTime'];
	    $policy = $stsConf['PolicyFile'];

    	while (1) {
	    	$forumRes = D('forum')->getlist(array('forum_status'=>3),'id as forum_id,forum_image,forum_voice,forum_content','20');
	    	if(!empty($forumRes)) {
		        foreach ($forumRes as $key => $value) {

					$iClientProfile = DefaultProfile::getProfile("cn-shanghai", $accessKeyID, $accessKeySecret);
					DefaultProfile::addEndpoint("cn-zhangjiakou", "cn-zhangjiakou", "Green", "green.cn-zhangjiakou.aliyuncs.com");
					$client = new DefaultAcsClient($iClientProfile);
					$request = new Green\ImageSyncScanRequest();
					$request->setMethod("POST");
					$request->setAcceptFormat("JSON");

					//判断文字
					if (!empty($value['forum_content'])) {
						$contentRes = $this->text($value['forum_content']);
						if ($contentRes !== 1) {
							$paramText = array('ali_examine_time'=>time(),'forum_status'=>2,'ali_examine'=>4,'ali_examine_imgjson'=>json_encode($contentRes));
							for ($i=0; $i < 3; $i++) { 
								$data = D('forum')->updateData(array('id'=>$value['forum_id']),$paramText);
								if ($data) {
									break;
								}
							}
						}
					}

					//组合图

					if (!empty($value['forum_image'])) {
						$imageArr = explode(',', $value['forum_image']);
						foreach ($imageArr as $k => $v) {
							$task[] = array(
								'dataId' =>  uniqid(),
							    'url' => $url.$v
							);
						}
						$request->setContent(json_encode(array("tasks" => $task,"scenes" => array("porn","terrorism"))));
					
						try {
						    $response = $client->getAcsResponse($request);
						    $img = 0;
						    // print_r($response);
						    $result = json_encode($response);
						    if(200 == $response->code){
						        $taskResults = $response->data;
						        foreach ($taskResults as $taskResult) {
						            if(200 == $taskResult->code || 404 == $taskResult->code){
						                $sceneResults = $taskResult->results;
						                foreach ($sceneResults as $sceneResult) {
						                    // $scene = $sceneResult->scene;
						                    $suggestion = $sceneResult->suggestion;
						                    if ($suggestion == 'block') {
						                    	// E('违规',1);
						                    	$img = 1;
						                    	break 2;
						                    }
						                }
						            }else{
						                // print_r("task process fail:" + $response->code);
						                E(json_encode($response),2);
						            }
						        }
						    }else{
						    	E(json_encode($response),2);
						        // print_r("detect not success. code:" + $response->code);
						    }

						    $param = array('ali_examine_time'=>time(),'forum_status'=>1,'ali_examine'=>1,'ali_examine_imgjson'=>json_encode($response));
						    if ($img == 1) {
						    	$param['forum_status'] = 2;
						    	$param['ali_examine'] = 2;
						    	for ($i=0; $i < 3; $i++) { 
									$data = D('forum')->updateData(array('id'=>$value['forum_id']),$param);
									if ($data) {
										break;
									}
								}
							    
						    }else{
						    	if (!empty($value['forum_voice'])) {
							    	$this->voice($value['forum_voice']);
							    }else{
							    	for ($i=0; $i < 3; $i++) { 
							    		$pass = D('forum')->updateData(array('id'=>$value['forum_id']),$param);
							    		if ($pass) {
											break;
										}
							    	}
							    }
						    }

						} catch (Exception $e) {
							// $param = array('ali_examine_time'=>time(),'forum_status'=>2);
							// if ($e ->getCode() == 1) {
							// 	$param['ali_examine'] = 2;
							// }else{
							// 	$param['ali_examine'] = 5;
							// }
							// for ($i=0; $i < 3; $i++) { 
							// 	$data = D('forum')->updateData(array('forum_id'=>$forumRes['forum_id']),$param);
							// 	if ($data) {
							// 		break;
							// 	}
							// }

						    $log = '/tmp/examineoss_'.date('Y-m-d',time()).'.log';
			        		file_put_contents($log,json_encode($e->getMessage()).PHP_EOL,FILE_APPEND); 
						}

					}else{
						if (!empty($value['forum_voice'])) {
							$this->voice($value['forum_voice']);
						}
					}
					
				}
		           
		    }else{
		    	sleep(60);
		    	// exit;
		    }
		    
	    }
	    exit;
	}


	public function voice($forum_voice)
	{
		// $url = 'http://muatest.oss-cn-zhangjiakou.aliyuncs.com/';
		$url = C('ALIYUNURL');
		$stsConf = C('STSCONF');
    	$accessKeyID = $stsConf['AccessKeyID'];
	    $accessKeySecret = $stsConf['AccessKeySecret'];
	    $roleArn = $stsConf['RoleArn'];
	    $tokenExpire = $stsConf['TokenExpireTime'];
	    $policy = $stsConf['PolicyFile'];
		//语音
		$iClientProfile = DefaultProfile::getProfile("cn-shanghai", $accessKeyID, $accessKeySecret);
		DefaultProfile::addEndpoint("cn-zhangjiakou", "cn-zhangjiakou", "Green", "green.cn-zhangjiakou.aliyuncs.com");
		$client = new DefaultAcsClient($iClientProfile);
	    $request_voice = new Green\VoiceAsyncScanRequest();
		$request_voice->setMethod("POST");
		$request_voice->setAcceptFormat("JSON");
		$task_voice = array('dataId' =>  uniqid(),
		    'url' => $url.$forum_voice,
		);
		$request_voice->setContent(json_encode(array("tasks" => array($task_voice),
		"scenes" => array("antispam"), "live" => false)));
		$response_voice = $client->getAcsResponse($request_voice);
	    // print_r($response);
	    if(200 == $response_voice->code){
	        $taskResults_voice = $response_voice->data;
	        foreach ($taskResults_voice as $taskResult_voice) {
	            if(200 == $taskResult_voice->code){
	                $taskId_voice = $taskResult_voice->taskId;
	                // 将taskId保存下来，间隔一段时间来轮询结果。
	                for ($m=0; $m < 3; $m++) { 
	                	$redisVal = base64_encode($taskId_voice).'_'.time();
	                	$res = RedisCache::getInstance()->getRedis()->LPUSH('forum_oss_voice_list',$redisVal);
	                	if ($res) {
	                		break;
	                	}
	                }
	            }else{
	                $log = '/tmp/examineoss_voice_'.date('Y-m-d',time()).'.log';
					file_put_contents($log,json_encode($response_voice).PHP_EOL,FILE_APPEND); 
	            }
	        }
	    }else{
	        $log = '/tmp/examineoss_voice_'.date('Y-m-d',time()).'.log';
			file_put_contents($log,json_encode($response_voice).PHP_EOL,FILE_APPEND); 
	    }
	}


	public function text($forum_content)
	{

		// $url = 'http://muatest.oss-cn-zhangjiakou.aliyuncs.com/';
		$url = C('ALIYUNURL');
		$stsConf = C('STSCONF');
    	$accessKeyID = $stsConf['AccessKeyID'];
	    $accessKeySecret = $stsConf['AccessKeySecret'];
	    $roleArn = $stsConf['RoleArn'];
	    $tokenExpire = $stsConf['TokenExpireTime'];
	    $policy = $stsConf['PolicyFile'];

		$iClientProfile = DefaultProfile::getProfile("cn-shanghai", $accessKeyID, $accessKeySecret);
		DefaultProfile::addEndpoint("cn-zhangjiakou", "cn-zhangjiakou", "Green", "green.cn-zhangjiakou.aliyuncs.com");
		$client = new DefaultAcsClient($iClientProfile);
		$request = new Green\TextScanRequest();
		$request->setMethod("POST");
		$request->setAcceptFormat("JSON");
		$task1 = array('dataId' =>  uniqid(),
		    'content' => $forum_content
		);

		$request->setContent(json_encode(array("tasks" => array($task1),
		    "scenes" => array("antispam"))));
		try {
		    $response = $client->getAcsResponse($request);
		    if(200 == $response->code){
		        $taskResults = $response->data;
		        foreach ($taskResults as $taskResult) {
		            if(200 == $taskResult->code){
		                $sceneResults = $taskResult->results;
		                foreach ($sceneResults as $sceneResult) {
		                    // $scene = $sceneResult->scene;
		                    $suggestion = $sceneResult->suggestion;
		                    if ($suggestion == 'block') {
		                    	return $response;
		                    }
		                }
		                
		            }else{
		                $log = '/tmp/examineoss_text_'.date('Y-m-d',time()).'.log';
		        		file_put_contents($log,json_encode($e->getMessage()).PHP_EOL,FILE_APPEND); 
		            }
		        }
		    }else{
		        $log = '/tmp/examineoss_text_'.date('Y-m-d',time()).'.log';
        		file_put_contents($log,json_encode($e->getMessage()).PHP_EOL,FILE_APPEND); 
		    }
		} catch (Exception $e) {
		    $log = '/tmp/examineoss_text_'.date('Y-m-d',time()).'.log';
    		file_put_contents($log,json_encode($e->getMessage()).PHP_EOL,FILE_APPEND); 
		}
		return 1;
	}




}



















