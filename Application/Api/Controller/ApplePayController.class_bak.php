<?php
namespace Api\Controller;

use Api\Service\ChargeiosService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Think\Exception;
use Think\Log;

class ApplePayController extends BaseController {

    protected static $list ; // 充值比例

    /**
     * 初始化配置信息
     * OrderAction constructor.
     */
    public function __construct()
    {
        // 实现父类的构造
        parent::__construct();
        // 获取支付比例.
        self::$list =D('chargeios')->getChargeList();

    }

    /**
     * IOS充值列表接口
     * @param $token        token值
     * @param $signature        签名md5(token)
     */
    public function chargelist($token,$signature){
        //获取数据
        $data = [
            "token" => I('post.token'),
            "signature" => I("post.signature"),
        ];
        try{
            //校验数据
            /*if($data['signature'] !== md5(strtolower($data['token']))){
                E("验签失败",2000);
            }*/
            $charge_list =  ChargeiosService::getInstance()->getlists();
            foreach($charge_list as $k=>$v){
                $charge_list[$k]['coinimg']=C('APP_URL').$v['coinimg'];
            }
            $result = [
                "charge_list"=>$charge_list,
            ];
            $this -> returnCode = 200;
            $this -> returnMsg = "操作成功";
            $this -> returnData = $result;

        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
    }

    /**
     * 苹果支付验证 接口
     * @param $token    token值
     * @param null $receipt 苹果支付验证
     * @param $rmb      金额
     * @param $user_id      用户id
     * @param $signature    签名
     * @param $transaction_id   苹果订单号
     * @param boolean $isSandbox 是否是沙盒模式,true,false
     * 21000 App Store不能读取你提供的JSON对象
     * 21002 receipt-data域的数据有问题
     * 21003 receipt无法通过验证
     * 21004 提供的shared secret不匹配你账号中的shared secret
     * 21005 receipt服务器当前不可用
     * 21006 receipt合法，但是订阅已过期。服务器接收到这个状态码时，receipt数据仍然会解码并一起发送
     * 21007 receipt是Sandbox receipt，但却发送至生产系统的验证服务
     * 21008 receipt是生产receipt，但却发送至Sandbox环境的验证服务
     */
    public function getreceiptdata($token=null,$receipt,$rmb=null,$signature=null,$user_id,$transaction_id){
        //$receipt = "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";
        //$receipt = "MIITpgYJKoZIhvcNAQcCoIITlzCCE5MCAQExCzAJBgUrDgMCGgUAMIIDRwYJKoZIhvcNAQcBoIIDOASCAzQxggMwMAoCAQgCAQEEAhYAMAoCARQCAQEEAgwAMAsCAQECAQEEAwIBADALAgEDAgEBBAMMATEwCwIBCwIBAQQDAgEAMAsCAQ8CAQEEAwIBADALAgEQAgEBBAMCAQAwCwIBGQIBAQQDAgEDMAwCAQoCAQEEBBYCNCswDAIBDgIBAQQEAgIAijANAgENAgEBBAUCAwHUwDANAgETAgEBBAUMAzEuMDAOAgEJAgEBBAYCBFAyNTMwGAIBAgIBAQQQDA5jb20ueGlueXVlLm11YTAYAgEEAgECBBC005eaXj/SaGeTHm9rXelZMBsCAQACAQEEEwwRUHJvZHVjdGlvblNhbmRib3gwHAIBBQIBAQQU+CuY4citCMNO6IA0KiabPjitSBQwHgIBDAIBAQQWFhQyMDE5LTA3LTEyVDEwOjQwOjQ5WjAeAgESAgEBBBYWFDIwMTMtMDgtMDFUMDc6MDA6MDBaMDICAQcCAQEEKvxO1Lba1gey2miSQkIi+akZ6dFLDPaCSV1j4Umo/RtRxe+4nhb8fjSDUTA/AgEGAgEBBDde4TqfPNHhHsuysxr/qlXnF8NwlCsqRynsZ8gEzRpJdeuy6lv2Yo54TUvD3Wy9L4rk94zL6/oGMIIBWAIBEQIBAQSCAU4xggFKMAsCAgasAgEBBAIWADALAgIGrQIBAQQCDAAwCwICBrACAQEEAhYAMAsCAgayAgEBBAIMADALAgIGswIBAQQCDAAwCwICBrQCAQEEAgwAMAsCAga1AgEBBAIMADALAgIGtgIBAQQCDAAwDAICBqUCAQEEAwIBATAMAgIGqwIBAQQDAgEBMAwCAgauAgEBBAMCAQAwDAICBq8CAQEEAwIBADAMAgIGsQIBAQQDAgEAMBsCAganAgEBBBIMEDEwMDAwMDA1NDYzNTcyMDUwGwICBqkCAQEEEgwQMTAwMDAwMDU0NjM1NzIwNTAeAgIGpgIBAQQVDBNjb20ueGlueXVlLm11YS4wNjQyMB8CAgaoAgEBBBYWFDIwMTktMDctMTJUMTA6NDA6NDlaMB8CAgaqAgEBBBYWFDIwMTktMDctMTJUMTA6NDA6NDlaoIIOZTCCBXwwggRkoAMCAQICCA7rV4fnngmNMA0GCSqGSIb3DQEBBQUAMIGWMQswCQYDVQQGEwJVUzETMBEGA1UECgwKQXBwbGUgSW5jLjEsMCoGA1UECwwjQXBwbGUgV29ybGR3aWRlIERldmVsb3BlciBSZWxhdGlvbnMxRDBCBgNVBAMMO0FwcGxlIFdvcmxkd2lkZSBEZXZlbG9wZXIgUmVsYXRpb25zIENlcnRpZmljYXRpb24gQXV0aG9yaXR5MB4XDTE1MTExMzAyMTUwOVoXDTIzMDIwNzIxNDg0N1owgYkxNzA1BgNVBAMMLk1hYyBBcHAgU3RvcmUgYW5kIGlUdW5lcyBTdG9yZSBSZWNlaXB0IFNpZ25pbmcxLDAqBgNVBAsMI0FwcGxlIFdvcmxkd2lkZSBEZXZlbG9wZXIgUmVsYXRpb25zMRMwEQYDVQQKDApBcHBsZSBJbmMuMQswCQYDVQQGEwJVUzCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBAKXPgf0looFb1oftI9ozHI7iI8ClxCbLPcaf7EoNVYb/pALXl8o5VG19f7JUGJ3ELFJxjmR7gs6JuknWCOW0iHHPP1tGLsbEHbgDqViiBD4heNXbt9COEo2DTFsqaDeTwvK9HsTSoQxKWFKrEuPt3R+YFZA1LcLMEsqNSIH3WHhUa+iMMTYfSgYMR1TzN5C4spKJfV+khUrhwJzguqS7gpdj9CuTwf0+b8rB9Typj1IawCUKdg7e/pn+/8Jr9VterHNRSQhWicxDkMyOgQLQoJe2XLGhaWmHkBBoJiY5uB0Qc7AKXcVz0N92O9gt2Yge4+wHz+KO0NP6JlWB7+IDSSMCAwEAAaOCAdcwggHTMD8GCCsGAQUFBwEBBDMwMTAvBggrBgEFBQcwAYYjaHR0cDovL29jc3AuYXBwbGUuY29tL29jc3AwMy13d2RyMDQwHQYDVR0OBBYEFJGknPzEdrefoIr0TfWPNl3tKwSFMAwGA1UdEwEB/wQCMAAwHwYDVR0jBBgwFoAUiCcXCam2GGCL7Ou69kdZxVJUo7cwggEeBgNVHSAEggEVMIIBETCCAQ0GCiqGSIb3Y2QFBgEwgf4wgcMGCCsGAQUFBwICMIG2DIGzUmVsaWFuY2Ugb24gdGhpcyBjZXJ0aWZpY2F0ZSBieSBhbnkgcGFydHkgYXNzdW1lcyBhY2NlcHRhbmNlIG9mIHRoZSB0aGVuIGFwcGxpY2FibGUgc3RhbmRhcmQgdGVybXMgYW5kIGNvbmRpdGlvbnMgb2YgdXNlLCBjZXJ0aWZpY2F0ZSBwb2xpY3kgYW5kIGNlcnRpZmljYXRpb24gcHJhY3RpY2Ugc3RhdGVtZW50cy4wNgYIKwYBBQUHAgEWKmh0dHA6Ly93d3cuYXBwbGUuY29tL2NlcnRpZmljYXRlYXV0aG9yaXR5LzAOBgNVHQ8BAf8EBAMCB4AwEAYKKoZIhvdjZAYLAQQCBQAwDQYJKoZIhvcNAQEFBQADggEBAA2mG9MuPeNbKwduQpZs0+iMQzCCX+Bc0Y2+vQ+9GvwlktuMhcOAWd/j4tcuBRSsDdu2uP78NS58y60Xa45/H+R3ubFnlbQTXqYZhnb4WiCV52OMD3P86O3GH66Z+GVIXKDgKDrAEDctuaAEOR9zucgF/fLefxoqKm4rAfygIFzZ630npjP49ZjgvkTbsUxn/G4KT8niBqjSl/OnjmtRolqEdWXRFgRi48Ff9Qipz2jZkgDJwYyz+I0AZLpYYMB8r491ymm5WyrWHWhumEL1TKc3GZvM";
        //获取数据
        $data_repose = [
//            "token" => I('post.token'),
            "receipt"=> I('post.receipt'),
//            "rmb" => I('post.rmb'),
//            "signature" => I("post.signature"),
            "user_id" => I("post.user_id"),
            "transaction_id" => I('post.transaction_id'),
        ];
        try{

            //检验数据
            $receipt = $data_repose['receipt'];
            //$receipt = "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";
            $user_id = RedisCache::getInstance()->get($data_repose['token']);
            //校验数据
//            if($data_repose['signature'] !== md5(strtolower($data_repose['user_id']))){
//                E("验签失败",2000);
//            }
            if(empty($data_repose['user_id'])){
                E("此用户异常数据",2000);
            }
            //判断当前用户是否存在
            $is_member = M('member')->where(array("id"=>$data_repose['user_id']))->find();
            if(empty($is_member)){
                E("该当前用户不存在",2000);
            }
            if(empty($receipt)){
                E("当前凭证不能为空",2000);
            }
            if(empty($data_repose['transaction_id'])){
                E("该当前订单不能为空",2000);
            }

//            $endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';       //沙盒模式
            // $isSandbox = true;//如果是沙盒模式，请求苹果测试服务器,反之，请求苹果正式的服务器
            // if ($isSandbox) {
            //     $endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';       //沙盒模式
            // }
            // else {
            //     $endpoint = 'https://buy.itunes.apple.com/verifyReceipt';       //苹果正式模式
            // }
            $endpoint = C('APPLEPAY_URL');
            $postData = json_encode(
                array('receipt-data' => $receipt)
            );
            //请求验证收据
            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $response = curl_exec($ch);
            $errno    = curl_errno($ch);
            $errmsg   = curl_error($ch);
            curl_close($ch);
            //$response = '{"receipt":{"receipt_type":"ProductionSandbox","adam_id":0,"app_item_id":0,"bundle_id":"com.xinyue.mua","application_version":"1","download_id":0,"version_external_identifier":0,"receipt_creation_date":"2019-07-12 04:52:16 Etc\/GMT","receipt_creation_date_ms":"1562907136000","receipt_creation_date_pst":"2019-07-11 21:52:16 America\/Los_Angeles","request_date":"2019-07-12 10:23:25 Etc\/GMT","request_date_ms":"1562927005434","request_date_pst":"2019-07-12 03:23:25 America\/Los_Angeles","original_purchase_date":"2013-08-01 07:00:00 Etc\/GMT","original_purchase_date_ms":"1375340400000","original_purchase_date_pst":"2013-08-01 00:00:00 America\/Los_Angeles","original_application_version":"1.0","in_app":[{"quantity":"1","product_id":"com.xinyue.mua.6","transaction_id":"1000000546194431","original_transaction_id":"1000000546194431","purchase_date":"2019-07-12 04:52:16 Etc\/GMT","purchase_date_ms":"1562907136000","purchase_date_pst":"2019-07-11 21:52:16 America\/Los_Angeles","original_purchase_date":"2019-07-12 04:52:16 Etc\/GMT","original_purchase_date_ms":"1562907136000","original_purchase_date_pst":"2019-07-11 21:52:16 America\/Los_Angeles","is_trial_period":"false"}]},"status":0,"environment":"Sandbox"}';

            $data = json_decode($response);
            file_put_contents("/tmp/applepay.log","Apply--".date("Y-m-d H:i:s",time()).":".json_encode($data)."".PHP_EOL,FILE_APPEND);
            //判断时候出错，抛出异常
            if ($errno != 0) {
                E($errmsg,$errno);
            }
            //判断返回的数据是否是对象
            if (!is_object($data)) {
                E('无效的响应数据',4000);
            }
            //判断购买时候成功
            if (!isset($data->status) || $data->status != 0) {
                E('无效收据',4000);
            }
            //转换成数组
            $result = $this->object_array_data($data);
            //查询凭证是否已被使用
            // $nums = count($result['receipt']['in_app'])-1;
            $in_app_count = count($result['receipt']['in_app']);
            $nums = $in_app_count;
            foreach ($result['receipt']['in_app'] as $key => $value) {
                # code...
                if ($value['transaction_id'] == $data_repose['transaction_id']) {
                    $nums = $key;
                    break;
                }
            }

            //判断当前订单号与苹果凭证订单号是否不致
            if ($nums >= $in_app_count) {
                E("该当前订单不存在",2000);
            }
            //查询对应当前的product_id 下的唯一标识
            $iosflag = $result['receipt']['in_app'][$nums]['product_id'];
            $receipt_ordersn = "APPLEPAY".$result['receipt']['in_app'][$nums]['transaction_id'];
            $count1 = M('chargedetail')->where(array("dealid"=>$receipt_ordersn))->count();
//            var_dump($count1);die();
            if($count1>=1){
                E("收据已被使用",4002);
            }
            //生成支付订单
            //开启事务并且生成支付订单
            $coin = M('chargeios')->where(array("iosflag"=>$iosflag))->getField("diamond");     //获取对应的虚拟币
            $rmb = M('chargeios')->where(array("iosflag"=>$iosflag))->getField("rmb");          //获取对应的充值金额
//            $coin = M('chargeios')->where(array("rmb"=>$data_repose['rmb']))->getField("diamond");
//            echo M('chargeios')->getLastSql();die();
            M()->startTrans();
            $orderNo = $this->createOrderNo($data_repose['user_id']);
            $dataes = [
                'uid'      => $data_repose['user_id'],
                'rmb'      => $rmb,
                'coin'     => $coin,
                'content'  => "苹果支付",
                'status'   => 1,         //订单状态 0未支付 1已支付
                'orderno'  => $orderNo,     //订单表
                'addtime'  => date('Y-m-d H:i:s',time()),
                'dealid'   => $receipt_ordersn,            //三方订单信息
                'platform' => 2,            //0支付宝 1微信 2苹果支付
                'channel' => $this->clientChannel,      //渠道
            ];
            $chargedetail = M('chargedetail')->add($dataes);
//            echo M('chargedetail')->getLastSql();die();
            //2.生成充值信息
            $beandetail =M('beandetail')->data(array(
                "action" => "charges",
                "uid" => $data_repose['user_id'],
                "content" => "增加M豆",
                "bean" => $coin,    //虚拟币
                "addtime" => date("Y-m-d H:i:s",time()),
            ))->add();
            //3.增加用户的虚拟币
            $member = M('member')->where(array("id"=>$data_repose['user_id']))->setInc('totalcoin',$coin);
            //获取对应当前用户充值成功后的虚拟币
            $updatecoin = M('member')->where(array("id"=>$data_repose['user_id']))->getField("totalcoin");
            $result_coin=[
                'coin'=>$updatecoin,
            ];
//            echo M('chargedetail')->getLastSql();die();
            if($chargedetail && $beandetail && $member){
                M()->commit();
                //如果以上三个都不成功,那么回滚事件,反之将成功信息写入日志数据
                $this -> returnCode = 200;
                $this -> returnMsg = "操作成功";
                $this -> returnData = $result_coin;
//                E("订单更新成功",200);
            }else{
                M()->rollback();
                E("订单更新失败",601);
            }
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();

        }
        $this -> returnData();
    }

    /**
     * 生成唯一的订单号
     */
    private function createOrderNo($user_id)
    {
        // 生成订单号.
        $orderNo = $user_id.time().rand(10000,99999);

        // 检查订单是否存在.
        $orderNoExist = $this->getOrderInfo(["orderno" => $orderNo]);
        // 如果生成失败，再次调用该方法.
        if ($orderNoExist) {
            $orderNo = $this->createOrderNo($user_id);
        }
        // var_dump($orderNo);die;
        return $orderNo;
    }
    /**
     * 查看系统订单信息.
     * @param $where array 系统订单的查询条件.
     */
    private function getOrderInfo($where)
    {//var_dump(123);die;
        $orderInfo =D('chargedetail')->getorder($where);
        //var_dump($orderInfo);die;
        return $orderInfo;
    }


    private function object_array_data($array) {
        if(is_object($array)) {
            $array = (array)$array;
        } if(is_array($array)) {
            foreach($array as $key=>$value) {
                $array[$key] = $this->object_array_data($value);
            }
        }
        return $array;
    }

}