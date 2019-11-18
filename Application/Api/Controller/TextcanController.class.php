<?php
namespace Api\Controller;
use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Common\Util\TextYun;
use Think\Log;
include_once 'aliyun-php-sdk-core/Config.php';
use Green\Request\V20180509 as Green;
use Green\Request\Extension\ClientUploader;
use DefaultProfile;
date_default_timezone_set('Asia/Shanghai');
class TextcanController extends BaseController {

    public function textcan($textcontent){


        //调取阿里云人内容安全
//        $ak["accessKeyId"] = "LTAIB1xELc9MzLx5";
//        $ak["accessKeySecret"] = "fMoCSlTQfoHDagZa45zfxgqfA6eNHS";
        $ak["accessKeyId"] = "LTAIXCdnCOjxkN7g";
        $ak["accessKeySecret"] = "ZGSeBjO0B4afiAbpCRgW5eRx94OBaJ";
        //请替换成您的accessKeyId、accessKeySecret
        $iClientProfile = DefaultProfile::getProfile("cn-shanghai", $ak["accessKeyId"],$ak["accessKeySecret"]);
        DefaultProfile::addEndpoint("cn-shanghai", "cn-shanghai", "Green", "green.cn-shanghai.aliyuncs.com");
        $client = new \DefaultAcsClient($iClientProfile);
        $request = new Green\TextScanRequest();
        $request->setMethod("POST");
        $request->setAcceptFormat("JSON");
        $task1 = array('dataId' =>  uniqid(),
//            'content' => '你真棒江泽民'
            'content' => $textcontent
        );

        /**
         * 文本垃圾检测： antispam
         **/
//        $request->setContent(json_encode(array("tasks" => array($task1),
//            "scenes" => array("antispam"))));
        $request->setContent(json_encode(array("tasks" => array($task1),
            "scenes" => array("antispam"),"bizType"=>"nickname")));
        try {
            $response = $client->getAcsResponse($request);
            if(200 == $response->code){
                $taskResults = $response->data;
                foreach ($taskResults as $taskResult) {
                    $taskId = $taskResult->taskId;
                    Log::record("内容安全_taskId：".$taskId, "INFO" );
                    if(200 == $taskResult->code){
                        $sceneResults = $taskResult->results;
                        foreach ($sceneResults as $sceneResult) {
                            $scene = $sceneResult->scene;
                            $suggestion = $sceneResult->suggestion;
                            return $suggestion;
                            //根据scene和suggetion做相关处理
                            //do something
                        }
                    }else{
                        print_r("task process fail:" + $response->code);
                    }
                }
            }else{
                print_r("detect not success. code:" + $response->code);
            }
        } catch (Exception $e) {
            print_r($e);
        }
    }


}


?>