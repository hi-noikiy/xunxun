<?php
namespace Api\Controller;
use Api\Service\MemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\Easemob;
use Think\Exception;
use Think\Log;

class AlicodeController extends Controller{
    
    protected static $list ; // 充值比例
    protected static $ALI_OPEN = array(); // 支付宝.
    const APP_ID = '2019070165765341';       //self::APP_ID   
    const TRANSFER = 'https://openapi.alipay.com/gateway.do?charset=utf-8';
    const APP_PUBLIC_PRIKEY = 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnuEYjjOn6joZwsjzOtE2/qW5ejy85x0wHp49hG+Q63fWJrSGvCRX1zTNlCaWggN/aA1LR0g55Vd/t9orPm4LtU0riFK6FnOzp48UR1IJQLeNqd1rDBR7BiXPJBTMZrcJWYnBtMLiKXWWLioWk1heywhlfLqCBUt4wg3gifonxJFbmSZWENgh29gjT69b4bqJ4+jrSx9nCertkXfsoJj8+XXiRRr8s9YYFRzhzIrtYaoaRdaxy4efzXDUBeKfzWL1vWBq2eHRPHL2njw8f9Y/KbgaJxu2TS5pXYCtJF7kSVZvKUil6XzlSibBdwCuy4RCCS4oN6lvB/X1gwOM1C8whwIDAQAB';
    const APPPRIKEY = 'MIIEowIBAAKCAQEAnuEYjjOn6joZwsjzOtE2/qW5ejy85x0wHp49hG+Q63fWJrSGvCRX1zTNlCaWggN/aA1LR0g55Vd/t9orPm4LtU0riFK6FnOzp48UR1IJQLeNqd1rDBR7BiXPJBTMZrcJWYnBtMLiKXWWLioWk1heywhlfLqCBUt4wg3gifonxJFbmSZWENgh29gjT69b4bqJ4+jrSx9nCertkXfsoJj8+XXiRRr8s9YYFRzhzIrtYaoaRdaxy4efzXDUBeKfzWL1vWBq2eHRPHL2njw8f9Y/KbgaJxu2TS5pXYCtJF7kSVZvKUil6XzlSibBdwCuy4RCCS4oN6lvB/X1gwOM1C8whwIDAQABAoIBAGv5eFG0A2rof3dkUADqDGD1Sd8sBglfScOVMSOfGrMcJxr41xRn8pacGRaVPvYu4FhbqIxSJp6ZX4AYMglkimp1fp9P8Y2upiq6z0JFG2qzFACcLNLx3EXqTiMsS1mHDUCfoVhylXctpZnMGrmadhmvpCEnM5Pbnb7r5Wx/6KTqPko/OjDIeLg+FarNvci+LQmspEPR7qH/Uxx3EaJhu+ZV6G2dOrcPNd8ahwX/BGpFKpPVHiwz3TA2fGH3qDrykkCneNpYi91t3OxDxxyaRVWTSFkVpLOtK89SKwukbAX4HHF9YXXk0HrAHtptNd7rUEXBIXHG2hDgoUHO5qYlWokCgYEAzve/32mQ96+dtDqelKNwsIpSKdEq0BcraJJYzBQxhmOxWIZOKch99wQzNmn8P1GlKaVRu5tgFExSbKv8X04llYTlnSLzRzZZZy8PGZtP86bRKloMUNPXWOHbF51EXKciD7UD1wDYLMmoR8QjA0YW/Ce2hblKpaiJ5ZcUiTbVbM0CgYEAxITcDfyH551+tlOZVJnp90kDn+mZWM86JckzYbhH0HrSwmjbCsBixkFV4FSYhIt1+fwCJ2MeqBleG8zDPbqXpjLNb7X4ORKFuEb3dKiFeqlD0jcSdJa6q6QpuSdNhqYDu5xJKNqj6fPXtjmvbW7asqyV485cPEoccvQFFTs2kqMCgYEAyNTroPUlMNN+qma/fuhh700pkV5gtu/ktWJdPBrUO939NzOMIUtlwA6ZS1Ho7eBh7ll3SB7rSocM7FqvWCPSoJYG4UYK361Db7bgZi6plHrpOLmMfdoyexMesHlw3p9nk+pIwZcWLc+4tXsDpqeaojA/Et/MKZezx8+ko8lLrHkCgYBwVh5QpHWv0dj4Mquor98Nq6A1zlwJZ1Qu+2eyyZvLsho+ZaAo8jbEa97CQLl6sxn6j7NPfpqsruub6p4E8F/18n57CENfpJXp9C9KcXbz2kRZq3+SRANrUIlFPRFVEht6KGmtv+YJO4mosir03HSJxJxeP717/UVr9M/fBh05DwKBgEX3vihQtbx0dBrgpwwnl/DzWxmcuh2MIIpeY2DDcK9qdNTwUo0BQvlgr8s0euO/UgTOi8k++jBlrRvEl51zt5XPub7HOtF4wW092aSccsnWso6HDYn9ACnIT8deod+1zwMlWCxmAkoXFvq1mN+Rq/gXWFH94j27eqUQredBpiD/';


    public function __construct(){
        parent::__construct();// 初始化父类的魔术方法.
        $ALIPAY = C("ALIPAY"); //开放平台
        // 微信开放平台配置(微信app支付).
        self::$ALI_OPEN = $ALIPAY;
        // // 获取支付比例.
        self::$list =D('charge')->getChargeList();
    }

    /**
     * 发起订单
     * @return array
     */
    public function publicPay()
    {

        $totalFee = $_REQUEST['rmb'];
        if (!session_id()) session_start();
        $selfuid = $_SESSION['selfuid'];
//        $selfuid = '1000064';
        $openid = $_SESSION['wxopid'];
        // $outTradeNo = $this->createOrderNo($selfuid);
        $orderName = "支付宝生活号代充";
        $notifyUrl = static::$ALI_OPEN['NOTIFY_URL'];
        $timestamp = time();

        $coin = self::$list[$totalFee]?self::$list[$totalFee]["diamond"]:0;
        if ($coin == 0) {
            E('支付比例错误',2003);
        }
        // 创建订单.
        $outTradeNo = $this->createOrder($selfuid,$totalFee,$coin);
        if (!$outTradeNo) {
            Log::record("创建订单失败----uid=". $selfuid.'---'.'支付宝生活号代充'.'---'.$totalFee, "INFO" );
            E('创建订单失败',2003);
        }
        //2.调用第三方
        //公共请求参数
        $pub_params = [
            'app_id'    => "2019070165765341",
            'method'    =>  'alipay.trade.page.pay', //接口名称 应填写固定值alipay.fund.trans.toaccount.transfer
            'format'    =>  'JSON', //目前仅支持JSON
            'charset'    =>  'UTF-8',
            'sign_type'    =>  'RSA2',//签名方式
            'sign'    =>  '', //签名
            'timestamp'    => date('Y-m-d H:i:s'), //发送时间 格式0000-00-00 00:00:00
            'version'    =>  '1.0', //固定为1.0
            'notify_url' => static::$ALI_OPEN['NOTIFY_URL'],
            'biz_content'    =>  '', //业务请求参数的集合
        ];

        //请求参数
        $api_params = [
            'out_trade_no'  => $outTradeNo,//订单号
            'product_code' => 'FAST_INSTANT_TRADE_PAY',     //销售产品码
            'total_amount' => $totalFee,   //金额
            'subject' => $orderName,        //标题
        ];
        //请求参数转换为json后和业务参数合并
        $pub_params['biz_content'] = json_encode($api_params,JSON_UNESCAPED_UNICODE);
        $pub_params =  $this->setRsa2Sign($pub_params);     //验签
        var_dump($selfuid);
        var_dump($pub_params);
        $resultes =  $this->curlRequest(self::TRANSFER, $pub_params);
        var_dump($resultes);
        $result = json_decode($resultes,true);
        echo "<pre>";
        var_dump($result);
        echo "</pre>";
//        echo $result['alipay_trade_precreate_response']['qr_code'];
    }

    /*
    * 生成订单号
    */
    private function createOrder($uid, $rmb, $coin){
        //生成订单号
        $orderNo = $this->createOrderNo($uid);
        $data = [
            'uid'      => $uid,
            'rmb'      => $rmb,
            'coin'     => $coin,
            'content'  => '支付宝生活号代充',
            'status'   => 0,
            'orderno'  => $orderNo,
            'addtime'  => date('Y-m-d H:i:s',time()),
            'dealid'   => 0,
            'platform' => 3,
            'title'    => '',
            "type"     => 1,        //1充值 2vip购买
            "is_active" =>0,         //is_active 状态 1续费vip 2激活vip 0充值
            "channel" => 'PublicNumber',
        ];
        $createOrderSuccessOrFail =D('chargedetail')->addData($data);
        // 创建订单成功，返回订单号.
        if ($createOrderSuccessOrFail) {
            return $orderNo;
        } else {
            return false;
        }

    }
    /*
     * 生成唯一的订单号
     */
    private function createOrderNo($uid)
    {
        // 生成订单号.
        $orderNo = $uid.time().rand(10000,99999);

        // 检查订单是否存在.
        $orderNoExist = $this->getOrderInfo(["orderno" => $orderNo]);
        // 如果生成失败，再次调用该方法.
        if ($orderNoExist) {
            $orderNo = $this->createOrderNo($uid);
        }
        return  $orderNo;
    }
    /*
     * 查看系统订单信息.
     *
     * @param $where array 系统订单的查询条件.
     */
    private function getOrderInfo($where)
    {
        $orderInfo =D('chargedetail')->getorder($where);
        return $orderInfo;
    }

    //获取含有签名的数组RSA
    public function setRsa2Sign($arr){
        $arr['sign'] = $this->getRsa2Sign($arr);
        return $arr;
    }

    //获取签名RSA2
    public function getRsa2Sign($arr){
        return $this->rsaSign($this->getStr($arr,'RSA2'), self::APPPRIKEY,'RSA2') ;
    }

    /**
     * RSA签名
     * @param $data 待签名数据
     * @param $private_key 私钥字符串
     * return 签名结果
     */
    public function rsaSign($data, $private_key,$type = 'RSA') {

        $search = [
            "-----BEGIN RSA PRIVATE KEY-----",
            "-----END RSA PRIVATE KEY-----",
            "\n",
            "\r",
            "\r\n"
        ];
        $private_key=str_replace($search,"",$private_key);
        $private_key=$search[0] . PHP_EOL . wordwrap($private_key, 64, "\n", true) . PHP_EOL . $search[1];
        $res=openssl_get_privatekey($private_key);
        if($res)
        {
            if($type == 'RSA'){
                openssl_sign($data, $sign,$res);
            }elseif($type == 'RSA2'){
                //OPENSSL_ALGO_SHA256
                openssl_sign($data, $sign,$res,OPENSSL_ALGO_SHA256);
            }
            openssl_free_key($res);
        }else {
            exit("私钥格式有误");
        }
        $sign = base64_encode($sign);
        return $sign;
    }

    public function getStr($arr,$type = 'RSA'){
        //筛选
        if(isset($arr['sign'])){
            unset($arr['sign']);
        }
        if(isset($arr['sign_type']) && $type == 'RSA'){
            unset($arr['sign_type']);
        }
        //排序
        ksort($arr);
        //拼接
        return  $this->getUrl($arr,false);
    }
    //将数组转换为url格式的字符串
    public function getUrl($arr,$encode = true){
        if($encode){
            return http_build_query($arr);
        }else{
            return urldecode(http_build_query($arr));
        }
    }

    /**curl地址发送
     * @param $url
     * @param string $data
     * @return mixed
     */
    public function curlRequest($url,$data = ''){
        $ch = curl_init();
        $params[CURLOPT_URL] = $url;    //请求url地址
        $params[CURLOPT_HEADER] = false; //是否返回响应头信息
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_FOLLOWLOCATION] = true; //是否重定向
        $params[CURLOPT_TIMEOUT] = 30; //超时时间
        if(!empty($data)){
            $params[CURLOPT_POST] = true;
            $params[CURLOPT_POSTFIELDS] = $data;
        }
        $params[CURLOPT_SSL_VERIFYPEER] = false;//请求https时设置,还有其他解决方案
        $params[CURLOPT_SSL_VERIFYHOST] = false;//请求https时,其他方案查看其他博文
        curl_setopt_array($ch, $params); //传入curl参数
        $content = curl_exec($ch); //执行
        curl_close($ch); //关闭连接
        //将数据双引号转化为单引号
        return $content;
    }

}