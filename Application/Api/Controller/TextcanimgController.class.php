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
class TextcanimgController extends BaseController {

    public function textSamples($url){

        //阿里云id与secret
//        $ak["accessKeyId"] = "8iT6snNIhWoKLa7r";
//        $ak["accessKeySecret"] = "v1heBQMu6YOdqgdYmfZalCTMvC1svn";
        $ak["accessKeyId"] = "LTAIXCdnCOjxkN7g";
        $ak["accessKeySecret"] = "ZGSeBjO0B4afiAbpCRgW5eRx94OBaJ";
        //请替换成您的accessKeyId、accessKeySecret
        $iClientProfile = DefaultProfile::getProfile("cn-shanghai", $ak["accessKeyId"],$ak["accessKeySecret"]);
        $result = DefaultProfile::addEndpoint("cn-shanghai", "cn-shanghai", "Green", "green.cn-shanghai.aliyuncs.com");
        $client = new \DefaultAcsClient($iClientProfile);
        $request = new Green\ImageSyncScanRequest();
        $request->setMethod("POST");
        $request->setAcceptFormat("JSON");
        $uploader = ClientUploader::getImageClientUploader($client);
//$url = $_SERVER['DOCUMENT_ROOT'].$url;
        $bytes = file_get_contents(C('APP_URLS').$url);
        $url = $uploader->uploadBytes($bytes);
        Log::record("传值image:".json_encode($url,true),"INFO");
//        $url = 'https://t1.hddhhn.com/uploads/tu/201612/276/st1.png';
        $task1 = array('dataId' =>  uniqid(),
//            'url' => 'https://ss0.bdstatic.com/94oJfD_bAAcT8t7mm9GUKT-xh_/timg?image&quality=100&size=b4000_4000&sec=1563781840&di=0241cf60bf61d2aec8238314ccc364eb&src=http://p1.ifengimg.com/fck/2018_01/4b3586c88209a81_w640_h429.jpg'
            'url' => $url
//            'url' => C('APP_URLS'). $url
        );
        // 设置待检测图片， 一张图片一个task，
        // 例如：检测2张图片，场景传递porn,terrorism，计费会按照2张图片鉴黄，2张图片暴恐检测计算
        $request->setContent(json_encode(array("tasks" => array($task1),
            "scenes" => array("porn","terrorism"))));
//            "scenes" => array("porn"))));
        try {
            $response = $client->getAcsResponse($request);
            Log::record("返回image:".json_encode($response,true),"INFO");
            if(200 == $response->code){
                $taskResults = $response->data;
                foreach ($taskResults as $taskResult) {
                    $taskId = $taskResult->taskId;
                    Log::record("内容安全_taskId：".$taskId, "INFO" );
                    if(200 == $taskResult->code){
                        $sceneResults = $taskResult->results;
                        foreach ($sceneResults as $key=>$sceneResult) {
                            $scene = $sceneResult->scene;
                            $suggestion = $sceneResult->suggestion;
                            // 根据scene和suggetion做相关处理
                            $result[$key]['scene'] = $sceneResult->scene;
                            $result[$key]['suggestion'] = $sceneResult->suggestion;
//                            var_dump($result[$key]['suggestion']);
//                            var_dump($result[$key]['suggestion']);
                        }
                        Log::record("返回结果：".$result[0]['suggestion'].'---'.$result[1]['suggestion'], "INFO" );
                        //file_put_contents("/tmp/image.log","image--".date("Y-m-d H:i:s",time()).":".$result[0]['suggestion'].'---'.$result[1]['suggestion']."".PHP_EOL,FILE_APPEND);
                        return $result[0]['suggestion'].",".$result[1]['suggestion'];
//                        var_dump($result[0]['suggestion'].",".$result[1]['suggestion']);die();
                    }else{
//                        print_r("task process fail:" + $response->code);
                        E("获取失败","1000");
                    }
                }
            }else{
//                print_r("detect not success. code:" + $response->code);
                E("获取失败","1000");
            }
        } catch (Exception $e) {
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
    }

}


?>