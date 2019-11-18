<?php
namespace Admin\Controller;
use Think\Controller;
use Think\Exception;
use Think\Log;

class ComController extends BaseController{
    /**
     * @param $appkey   应用ID
     * @param $creattime    开始时间
     * @param $endtime      结束时间
     * @throws
     */
    public function comdata($appkey,$todaytime,$endtime){
        $data_umengs = [
            "appkey" => $appkey,
            "todaytime" => $todaytime,
            "endtime" => $endtime,
        ];
//        print_r($data_umengs);die();
        try {
            // ---------------------------example start---------------------------------
//            include_once "./ThinkPHP/Library/Vendor/com/umeng/uapp/param/UmengUappGetDurationsParam.class.php";
//            include_once "./ThinkPHP/Library/Vendor/com/umeng/uapp/param/UmengUappGetDurationsResult.class.php"; /ThinkPHP/Library/Vendor/com/umeng/uapp/param/UmengUappGetRetentionsResult.class.php

            include_once ('./ThinkPHP/Library/Vendor/com/umeng/uapp/param/UmengUappGetRetentionsParam.class.php');
            include_once ('./ThinkPHP/Library/Vendor/com/umeng/uapp/param/UmengUappGetRetentionsResult.class.php');

            include_once "./ThinkPHP/Library/Vendor/com/alibaba/openapi/client/policy/RequestPolicy.class.php";
            include_once "./ThinkPHP/Library/Vendor/com/alibaba/openapi/client/entity/ByteArray.class.php";
            include_once "./ThinkPHP/Library/Vendor/com/alibaba/openapi/client/util/DateUtil.class.php";
            include_once "./ThinkPHP/Library/Vendor/com/alibaba/openapi/client/policy/ClientPolicy.class.php";
            include_once "./ThinkPHP/Library/Vendor/com/alibaba/openapi/client/policy/ClientPolicy.class.php";

            include_once "./ThinkPHP/Library/Vendor/com/alibaba/openapi/client/APIRequest.class.php";
            include_once "./ThinkPHP/Library/Vendor/com/alibaba/openapi/client/APIId.class.php";
            include_once "./ThinkPHP/Library/Vendor/com/alibaba/openapi/client/SyncAPIClient.class.php";

            include_once "./ThinkPHP/Library/Vendor/com/alibaba/openapi/client/policy/DataProtocol.class.php";
            include_once "./ThinkPHP/Library/Vendor/com/alibaba/openapi/client/serialize/SerializerProvider.class.php";
            include_once "./ThinkPHP/Library/Vendor/com/alibaba/openapi/client/serialize/SerializerProvider.class.php";
            include_once "./ThinkPHP/Library/Vendor/com/alibaba/openapi/client/serialize/DeSerializer.class.php";
            include_once "./ThinkPHP/Library/Vendor/com/alibaba/openapi/client/util/SignatureUtil.class.php";

            include_once "./ThinkPHP/Library/Vendor/com/umeng/uapp/param/UmengUappGetRetentionsParam.class.php";
            include_once "./ThinkPHP/Library/Vendor/com/umeng/uapp/param/UmengUappRetentionInfo.class.php";

            // 请替换第一个参数apiKey和第二个参数apiSecurity
            $apiKey = "6263252";
            $apiSecurity = "BLxZfEz3PaZ";
            $clientPolicy = new \ClientPolicy ($apiKey,$apiSecurity, 'gateway.open.umeng.com');
            $syncAPIClient = new \SyncAPIClient ( $clientPolicy );
            $reqPolicy = new \RequestPolicy();
            $reqPolicy->httpMethod = "POST";
            $reqPolicy->needAuthorization = false;
            $reqPolicy->requestSendTimestamp = false;
            // 测试环境只支持http
            // $reqPolicy->useHttps = false;
            $reqPolicy->useHttps = true;
            $reqPolicy->useSignture = true;
            $reqPolicy->accessPrivateApi = false;

            // --------------------------构造参数----------------------------------
//            var_dump($creattimes);die();
            $param = new \UmengUappGetRetentionsParam();
//            $param->setAppkey("5ce644560cafb24e1d0000d5");
//            $param->setStartDate("2019-08-27");
//            $param->setEndDate("2019-08-27");
//            var_dump($creattimes);die();
            $param->setAppkey($data_umengs['appkey']);
            $param->setStartDate($data_umengs['todaytime']);
            $param->setEndDate($data_umengs['endtime']);
            $param->setPeriodType("daily");
            $param->setChannel("");
            $param->setVersion("");
            $param->setType("newUser");
            // --------------------------构造请求----------------------------------
//            print_r($data_umengs);die();
            $request = new \APIRequest ();
            $apiId = new \APIId ("com.umeng.uapp", "umeng.uapp.getRetentions", 1 );
            $request->apiId = $apiId;
            $request->requestEntity = $param;
            // --------------------------构造结果----------------------------------
            $result = new \UmengUappGetRetentionsResult();
            $result_umeng = $syncAPIClient->send ( $request, $result, $reqPolicy );
            $result_umeng = (array)$result_umeng;
//            $aa = $this->object_to_array($result_umeng);
            $aa = json_decode(json_encode($result_umeng), true);
            foreach($aa as $k=>$v){
                foreach($v as $key=>$value){
//                    print_r($value);
                    $results['totalInstallUser'] =  $value[0]['totalInstallUser'];
                    $results['retentionRate'] =  $value[0]['retentionRate'][0];
                }
//                return $results['totalInstallUser'].",".$results['retentionRate'];
            }
            $result_umengs = $results['totalInstallUser'].",".$results['retentionRate'];
            return $result_umengs;


//            print_r($result_umengs);die();
            // ----------------------------example end-------------------------------------
        } catch ( OceanException $ex ) {
            echo "Exception occured with code[";
            echo $ex->getErrorCode ();
            echo "] message [";
            echo $ex->getMessage ();
            echo "].";
        }
    }

    private function object_to_array($e) {
        $_arr = is_object($e) ? get_object_vars($e) : $e;
        foreach ($_arr as $key => $val) {
            $val = (is_array($val) || is_object($val)) ? $this->object_to_array($val) : $val;
            $arr[$key] = $val;
        }
        return $arr;
    }

    private function arrayobject($data){
        foreach($data as $k=>$v){
            if(is_object($v)){
                array($v);
                $this->arrayobject($v);
            }else{
                return $data;
            }

        }
    }


}
