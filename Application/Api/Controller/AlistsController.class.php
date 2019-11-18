<?php
namespace Api\Controller;

include_once 'aliyun-php-sdk-core/Config.php';
use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Think\Log;
use Sts\Request\V20150401 as Sts;
use DefaultProfile;
use DefaultAcsClient;
class AlistsController extends BaseController {
	
    public function getStsToken(){
	// $myjsonarray =  array(
 //        "AccessKeyID" => "LTAIWDuY7Omo3ITY",
 //        "AccessKeySecret" => "P3libDDVGbz6kKWPg3oghj0Pfr1yhB",
 //        "RoleArn" => "acs:ram::1313844168009472:role/oss",
 //        "BucketName" => "muatest",
 //        "Endpoint" => "oss-cn-zhangjiakou.aliyuncs.com",
 //        "TokenExpireTime" => "900",
 //        "PolicyFile"=> '{
	// 		"Statement": [
	// 		    {
	// 		      "Action": [
	// 		        "oss:*"
	// 		      ],
	// 		      "Effect": "Allow",
	// 		      "Resource": ["acs:oss:*:*:*"]
	// 		    }
	// 		  ],
	// 		"Version": "1"
	// 	}'
 //   	);
    $stsConf = C('STSCONF');
	$accessKeyID = $stsConf['AccessKeyID'];
    $accessKeySecret = $stsConf['AccessKeySecret'];
    $roleArn = $stsConf['RoleArn'];
    $tokenExpire = $stsConf['TokenExpireTime'];
    $policy = $stsConf['PolicyFile'];

    $iClientProfile = DefaultProfile::getProfile("cn-hangzhou", $accessKeyID, $accessKeySecret);
    $client = new DefaultAcsClient($iClientProfile);

    $request = new Sts\AssumeRoleRequest();
    $request->setRoleSessionName("client_name");
    $request->setRoleArn($roleArn);
    $request->setPolicy($policy);
    $request->setDurationSeconds($tokenExpire);
    $response = $client->doAction($request);

    $data = array();
    $body = $response->getBody();
    $content = json_decode($body);
    if ($response->getStatus() == 200)
    {
        // $data['StatusCode'] = 200;
        $data['AccessKeyId'] = $content->Credentials->AccessKeyId;
        $data['AccessKeySecret'] = $content->Credentials->AccessKeySecret;
        $data['Expiration'] = 'http://'.$stsConf['Endpoint'];
        $data['SecurityToken'] = $content->Credentials->SecurityToken;
        $data['Endpoint'] = $content->Credentials->Expiration;
        $data['BucketName'] = $stsConf['BucketName'];
        $data['path'] = $stsConf['path'];
        // $data['dir'] = 'dongtai/';
        $this -> returnCode = 200;
        $this -> returnData = $data;
        $this->returnData();
    }
    else
    {
        $this -> returnCode = 500;
        $this -> returnMsg = '操作失败';
        $this->returnData();
    }
    // echo json_encode($data);
    return;









    }

    






}



















