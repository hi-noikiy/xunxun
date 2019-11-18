<?php
namespace Api\Controller;

include_once 'aliyun-php-sdk-core/Config.php';
use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Think\Log;
use Common\Util\RedisCache;
use Api\Service\PushMsgService;
use Green\Request\V20180509 as Green;
use Green\Request\Extension\ClientUploader;
use DefaultProfile;
use DefaultAcsClient;

class UserController extends BaseController {

    //检测头像
    public function setavatar()
    {
        $token = I('post.token');
        $avatar = I('post.avatar');
        try {
            if (!$token || !$avatar) {
                E("参数错误", 500);
            }
            $userid = RedisCache::getInstance()->get($token);
            if (!$userid) {
                E("用户不存在", 500);
            }
            $user_info = D('member')->getOneById($userid); 
            if (empty($user_info)) {
             	E("用户不存在", 500);
            } 
            $imgRes = $this->getoss([$avatar]);
            if ($imgRes) {
                $avatarData = D('member')->updateDate($userid,['avatar'=>'/'.$avatar]); 
                if ($avatarData) {
                    RedisCache::getInstance()->getRedis()->hset('userinfo_'.$userid,'avatar','/'.$avatar);
                    $rdsRoom = RedisCache::getInstance()->getRedis()->hGet('UserCurrentRoom',$user_info['id']);
                    if (!empty($rdsRoom)) {
                        // $msg = ['uid'=>$user_info['id'],'avatar'=>C('APP_URL_image').'/'.$avatar,'nickname'=>$user_info['nickname']];
                        $msg = ['uid'=>$user_info['id'],'avatar'=>$avatar];
                        $msg = json_encode($msg);
                        $queue = 'roomid_'.$rdsRoom;
                        PushMsgService::getInstance()->send($queue,$msg);
                    }
                    $this -> returnCode = 200;
                    $this -> returnMsg = '更新成功';
                }else{
                    $this -> returnCode = 500;
                    $this -> returnMsg = '图片保存失败';
                }
            } else {
                $this -> returnCode = 500;
                $this -> returnMsg = '图片检测失败';
            }
            $this->returnData();
        } catch (Exception $e) {
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
        
    }


    private function getoss($imageArr)
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
        $request = new Green\ImageSyncScanRequest();
        $request->setMethod("POST");
        $request->setAcceptFormat("JSON");
        foreach ($imageArr as $k => $v) {
            $task[] = array(
                'dataId' =>  uniqid(),
                'url' => $url.$v
            );
        }
        $request->setContent(json_encode(array("tasks" => $task,"scenes" => array("porn","terrorism"))));
        try {
            $response = $client->getAcsResponse($request);
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
                                $log = '/tmp/avatar_oss_'.date('Y-m-d',time()).'.log';
                                file_put_contents($log,json_encode($response).PHP_EOL,FILE_APPEND); 
                                return false;
                            }
                        }
                        return true;
                    }else{
                        $log = '/tmp/avatar_oss_'.date('Y-m-d',time()).'.log';
                        file_put_contents($log,json_encode($response).PHP_EOL,FILE_APPEND); 
                        return false;
                    }
                }
            }else{
                $log = '/tmp/avatar_oss_'.date('Y-m-d',time()).'.log';
                file_put_contents($log,json_encode($response).PHP_EOL,FILE_APPEND); 
                return false;
            }
        } catch (Exception $e) {
            $log = '/tmp/avatar_oss_'.date('Y-m-d',time()).'.log';
            file_put_contents($log,json_encode($e->getMessage()).PHP_EOL,FILE_APPEND); 
            return false;
        }

    }

    //实名认证
    public function realuser()
    {
        $token = I('post.token');
        $idcard = I('post.idcard');
        $name = I('post.name');
        try {
            if (!$token || !$idcard || !$name) {
                E("参数错误", 500);
            }
            $preg_card='/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}(\d|x|X)$/';
            if(!preg_match($preg_card,$idcard)){
                E("身份证格式不正确", 500);
            }
            $userid = RedisCache::getInstance()->get($token);
            $userInfo = D('member')->getOneById($userid);
            if (empty($userInfo)) {
                E("用户不存在", 500);
            }
            if ($userInfo['attestation'] == 1) {
                E("您已经认证完成", 500);
            }
            $redisKey = 'user_idcard_'.$userid;
            $rdsData = RedisCache::getInstance()->get($redisKey);
            $t = 24*3600;
            if (!empty($rdsData)) {
                if ($rdsData > 5) {
                    E("24小时认证次数超限", 500);
                }
                $v = $rdsData+1;
                RedisCache::getInstance()->getRedis()->setex($redisKey,$t,$v);
            }else{
                RedisCache::getInstance()->getRedis()->setex($redisKey,$t,1);
            }
            
            $idcard = trim($idcard);
            $name = trim($name);

            $host = "https://idcert.market.alicloudapi.com";
            $path = "/idcard";
            $method = "GET";
            $appcode = C('REALNAME');
            $headers = array();
            array_push($headers, "Authorization:APPCODE " . $appcode);
            $querys = "idCard=".$idcard."&name=".$name;
            $bodys = "";
            $url = $host . $path . "?" . $querys;

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            //curl_setopt($curl, CURLOPT_HEADER, true);
            //状态码: 200 正常；400 URL无效；401 appCode错误； 403 次数用完； 500 API网管错误
            if (1 == strpos("$".$host, "https://"))
            {
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            }
            $out_put = curl_exec($curl);
            // echo $out_put;
            $data = json_decode($out_put,true);
            if (!empty($data)) {
                if ($data['status'] == '01') {
                    $res = D('member')->updateDate($userid,array('attestation'=>1));
                    if ($res) {
                        $this -> returnCode = 200;
                        $this -> returnMsg = '认证成功';
                        RedisCache::getInstance()->getRedis()->hset('userinfo_'.$userid,'attestation',1);
                    }
                    $data['create_time'] = time();
                    D("user_card")->addData($data);
                    $this->returnData();
                }
            }
            $this -> returnCode = 500;
            $this -> returnMsg = '认证失败';
        } catch (Exception $e) {
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
    }






}


?>