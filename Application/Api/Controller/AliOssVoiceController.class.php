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
class AliOssVoiceController extends Controller {
	
	//语音轮询
    function getOssVoice() {
    	// $url = 'http://muatest.oss-cn-zhangjiakou.aliyuncs.com/';
    	$url = C('ALIYUNURL');
    	$stsConf = C('STSCONF');
    	$accessKeyID = $stsConf['AccessKeyID'];
	    $accessKeySecret = $stsConf['AccessKeySecret'];
	    $roleArn = $stsConf['RoleArn'];
	    $tokenExpire = $stsConf['TokenExpireTime'];
	    $policy = $stsConf['PolicyFile'];

	    $iClientProfile = DefaultProfile::getProfile("cn-shanghai", $accessKeyID, $accessKeySecret);
	    // DefaultProfile::addEndpoint("cn-shanghai", "cn-shanghai", "Green", "green.cn-shanghai.aliyuncs.com");
	    DefaultProfile::addEndpoint("cn-zhangjiakou", "cn-zhangjiakou", "Green", "green.cn-zhangjiakou.aliyuncs.com");
	    $client = new DefaultAcsClient($iClientProfile);


	    $redisData = RedisCache::getInstance()->getRedis()->RPOP('forum_oss_voice_list');
	    if (empty($redisData)) {
	    	sleep(10);
	    }else{
	    	$time = time();
	    	$res = explode('_', $redisData);
	    	$isStart = $time - $res[1];
	    	if ($isStart > 60) {
	    		$task_id = base64_decode($res[0]);
			    if (strlen($task_id) > 0) {
			        // while (true) {
			            list($code,$response) = $this->get_task($client, $task_id);
			            // if ($code == 280) {
			            //     print_r("Scanning\n");
			            //     sleep(10);
			            // } else 
			            if (!$code) {//修改数据库状态
			            	$param = array('ali_examine_time'=>time(),'forum_status'=>1,'ali_examine'=>1,'ali_examine_voicejson'=>json_encode($response));
			                $data = D('forum')->updateData(array('forum_id'=>$value['forum_id']),$param);
			                // break;
			            } else {
			                $log = '/tmp/examineoss_voice_task_'.date('Y-m-d',$time).'.log';
							file_put_contents($log,json_encode($code).PHP_EOL,FILE_APPEND); 
			                // break;
			            }
			        // }
			    }
	    	} else {
	    		for ($m=0; $m < 3; $m++) { 
                	$redisVal = $res[0].'_'.time();
                	$back = RedisCache::getInstance()->getRedis()->LPUSH('forum_oss_voice_list',$redisData);
                	if ($back) {
                		break;
                	}
                }
	    	}
	    	
	    	
	    }
	    
	}

	public function get_task($client, $task_id) {
	    $request = new Green\VoiceAsyncScanResultsRequest();
	    $request->setMethod("POST");
	    $request->setAcceptFormat("JSON");

	    $request->setContent(json_encode(array($task_id)));
	    try {
	        $response = $client->getAcsResponse($request);
	        // print_r($response);
	        if(200 == $response->code){
	            $taskResults = $response->data;
	            foreach ($taskResults as $taskResult) {
	                if(200 == $taskResult->code){
	                    $sceneResults = $taskResult->results;
	                    foreach ($sceneResults as $sceneResult) {
	                        $scene = $sceneResult->scene;
	                        $suggestion = $sceneResult->suggestion;
	                        // print_r($scene);
	                        // print_r($suggestion);
	                        return [$suggestion,$response];
	                    }
	                }else{
	                    // print_r("task process fail:" + $response->code);
	                    $log = '/tmp/examineoss_voice_task_'.date('Y-m-d',$time).'.log';
						file_put_contents($log,json_encode($response).PHP_EOL,FILE_APPEND); 
	                }
	                // return $taskResult->code;
	                return false;
	            }
	        }else{
	            // print_r("detect not success. code:" + $response->code);
	            $log = '/tmp/examineoss_voice_task_'.date('Y-m-d',$time).'.log';
				file_put_contents($log,json_encode($response).PHP_EOL,FILE_APPEND); 
	            // return $response->code;
	            return false;
	        }
	    } catch (Exception $e) {
	        $log = '/tmp/examineoss_voice_task_'.date('Y-m-d',$time).'.log';
			file_put_contents($log,json_encode($e).PHP_EOL,FILE_APPEND); 
	    }
	    return false;
	}




}



















