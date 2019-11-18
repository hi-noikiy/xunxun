<?php
namespace Api\Controller;
use Api\Service\MemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\Easemob;
use Think\Exception;
use Think\Log;
class AlishhController extends Controller{
    protected static $list ; // 充值比例
    protected static $ALI_OPEN = array(); // 微信公众号.

    public function __construct(){
        parent::__construct();// 初始化父类的魔术方法.
        $ALIPAY = C("ALIPAY"); //开放平台
        // 微信开放平台配置(微信app支付).
        self::$ALI_OPEN = $ALIPAY;
        // // 获取支付比例.
        self::$list =D('charge')->getChargeList();
    }

     public function pay(){
        header("Content-type: text/html; charset=utf-8");
        vendor('alipay.AlipayTradeService');
        vendor('alipay.AlipayTradeWapPayContentBuilder');
        vendor('alipay.AlipayTradeService');
        //配置文件
        $config['app_id']                  =   static::$ALI_OPEN['APP_ID'];  //appid
        $config['merchant_private_key']    =   'MIIEowIBAAKCAQEAnuEYjjOn6joZwsjzOtE2/qW5ejy85x0wHp49hG+Q63fWJrSG
vCRX1zTNlCaWggN/aA1LR0g55Vd/t9orPm4LtU0riFK6FnOzp48UR1IJQLeNqd1r
DBR7BiXPJBTMZrcJWYnBtMLiKXWWLioWk1heywhlfLqCBUt4wg3gifonxJFbmSZW
ENgh29gjT69b4bqJ4+jrSx9nCertkXfsoJj8+XXiRRr8s9YYFRzhzIrtYaoaRdax
y4efzXDUBeKfzWL1vWBq2eHRPHL2njw8f9Y/KbgaJxu2TS5pXYCtJF7kSVZvKUil
6XzlSibBdwCuy4RCCS4oN6lvB/X1gwOM1C8whwIDAQABAoIBAGv5eFG0A2rof3dk
UADqDGD1Sd8sBglfScOVMSOfGrMcJxr41xRn8pacGRaVPvYu4FhbqIxSJp6ZX4AY
Mglkimp1fp9P8Y2upiq6z0JFG2qzFACcLNLx3EXqTiMsS1mHDUCfoVhylXctpZnM
GrmadhmvpCEnM5Pbnb7r5Wx/6KTqPko/OjDIeLg+FarNvci+LQmspEPR7qH/Uxx3
EaJhu+ZV6G2dOrcPNd8ahwX/BGpFKpPVHiwz3TA2fGH3qDrykkCneNpYi91t3OxD
xxyaRVWTSFkVpLOtK89SKwukbAX4HHF9YXXk0HrAHtptNd7rUEXBIXHG2hDgoUHO
5qYlWokCgYEAzve/32mQ96+dtDqelKNwsIpSKdEq0BcraJJYzBQxhmOxWIZOKch9
9wQzNmn8P1GlKaVRu5tgFExSbKv8X04llYTlnSLzRzZZZy8PGZtP86bRKloMUNPX
WOHbF51EXKciD7UD1wDYLMmoR8QjA0YW/Ce2hblKpaiJ5ZcUiTbVbM0CgYEAxITc
DfyH551+tlOZVJnp90kDn+mZWM86JckzYbhH0HrSwmjbCsBixkFV4FSYhIt1+fwC
J2MeqBleG8zDPbqXpjLNb7X4ORKFuEb3dKiFeqlD0jcSdJa6q6QpuSdNhqYDu5xJ
KNqj6fPXtjmvbW7asqyV485cPEoccvQFFTs2kqMCgYEAyNTroPUlMNN+qma/fuhh
700pkV5gtu/ktWJdPBrUO939NzOMIUtlwA6ZS1Ho7eBh7ll3SB7rSocM7FqvWCPS
oJYG4UYK361Db7bgZi6plHrpOLmMfdoyexMesHlw3p9nk+pIwZcWLc+4tXsDpqea
ojA/Et/MKZezx8+ko8lLrHkCgYBwVh5QpHWv0dj4Mquor98Nq6A1zlwJZ1Qu+2ey
yZvLsho+ZaAo8jbEa97CQLl6sxn6j7NPfpqsruub6p4E8F/18n57CENfpJXp9C9K
cXbz2kRZq3+SRANrUIlFPRFVEht6KGmtv+YJO4mosir03HSJxJxeP717/UVr9M/f
Bh05DwKBgEX3vihQtbx0dBrgpwwnl/DzWxmcuh2MIIpeY2DDcK9qdNTwUo0BQvlg
r8s0euO/UgTOi8k++jBlrRvEl51zt5XPub7HOtF4wW092aSccsnWso6HDYn9ACnI
T8deod+1zwMlWCxmAkoXFvq1mN+Rq/gXWFH94j27eqUQredBpiD/';   //RSA2(SHA256)密钥
        $config['charset']                 =   "UTF-8";            //编码格式
        $config['sign_type']               =   "RSA2";             //签名方式
        $config['notify_url']              =   static::$ALI_OPEN['NOTIFY_URL'];    //异步地址
        $config['return_url']              =   static::$ALI_OPEN['NOTIFY_URL'];   //同步跳转
        $config['gatewayUrl']              =   "https://openapi.alipay.com/gateway.do";   //支付宝网关
        $config['alipay_public_key']       =   'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnuEYjjOn6joZwsjzOtE2
/qW5ejy85x0wHp49hG+Q63fWJrSGvCRX1zTNlCaWggN/aA1LR0g55Vd/t9orPm4L
tU0riFK6FnOzp48UR1IJQLeNqd1rDBR7BiXPJBTMZrcJWYnBtMLiKXWWLioWk1he
ywhlfLqCBUt4wg3gifonxJFbmSZWENgh29gjT69b4bqJ4+jrSx9nCertkXfsoJj8
+XXiRRr8s9YYFRzhzIrtYaoaRdaxy4efzXDUBeKfzWL1vWBq2eHRPHL2njw8f9Y/
KbgaJxu2TS5pXYCtJF7kSVZvKUil6XzlSibBdwCuy4RCCS4oN6lvB/X1gwOM1C8w
hwIDAQAB';   //支付宝公钥
 
        $data = session('date');
        $data1 = session('data1');
        if($data){
            $id = $data['balanceid'];
            $order = M('balance')->where(array('bpid'=>$id))->find();
        }
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $order['bpno'];
        //订单名称，必填
        $subject = "用户充值！";
        //付款金额，必填
        $total_amount = $order['bpprice'];
 
        //商品描述，可空
        $body = "";
 
        //超时时间
        $timeout_express="1m";
        $payRequestBuilder = new \AlipayTradeWapPayContentBuilder();
        $payRequestBuilder->setBody($body);
        $payRequestBuilder->setSubject($subject);
        $payRequestBuilder->setOutTradeNo($out_trade_no);
        $payRequestBuilder->setTotalAmount($total_amount);
        $payRequestBuilder->setTimeExpress($timeout_express);
        $payResponse = new \AlipayTradeService($config);
        $payResponse->__construct($config);
        $result=$payResponse->wapPay($payRequestBuilder,$config['return_url'],$config['notify_url']);
        return ;
 
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
            Log::record("创建订单失败----uid=". $uid.'---'.'支付宝生活号代充'.'---'.$totalFee, "INFO" );
            E('创建订单失败',2003);
        }



        //请求参数
        $requestConfigs = array(
            'out_trade_no'=>$outTradeNo,
            'product_code'=>'QUICK_WAP_WAY',
            'total_amount'=>$totalFee, //单位 元
            'subject'=>$orderName,  //订单标题
        );
        $commonConfigs = array(
            //公共参数
            'app_id' => static::$ALI_OPEN['APP_ID'],
            'method' => 'alipay.trade.wap.pay',             //接口名称
            'format' => 'JSON',
            'return_url' => static::$ALI_OPEN['RETURN_URL'],
            'charset'=>'utf-8',
            'sign_type'=>'RSA2',
            'timestamp'=>date('Y-m-d H:i:s'),
            'version'=>'1.0',
            'notify_url' => static::$ALI_OPEN['NOTIFY_URL'],
            'biz_content'=>json_encode($requestConfigs),
        );
        $commonConfigs["sign"] = $this->generateSign($commonConfigs, $commonConfigs['sign_type']);
        echo $this->buildRequestForm($commonConfigs);
        return;
        return $this->buildRequestForm($commonConfigs);
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @return 提交表单HTML文本
     */
    protected function buildRequestForm($para_temp) {
        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='https://openapi.alipay.com/gateway.do?charset=utf-8' method='POST'>";
        foreach($para_temp as $key=>$val){
            if (false === $this->checkEmpty($val)) {
                $val = str_replace("'","&apos;",$val);
                $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
            }       
        }
        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit' value='ok' style='display:none;''></form>";
        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";
        return $sHtml;
    }
    public function generateSign($params, $signType = "RSA") {
        return $this->sign($this->getSignContent($params), $signType);
    }
    protected function sign($data, $signType = "RSA") {
        $rsaPrivateKey = static::$ALI_OPEN['PRIVATEKEY'];
        $priKey=$rsaPrivateKey;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        ($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, version_compare(PHP_VERSION,'5.4.0', '<') ? SHA256 : OPENSSL_ALGO_SHA256); //OPENSSL_ALGO_SHA256是php5.4.8以上版本才支持
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }
    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;
        return false;
    }
    public function getSignContent($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                // 转换成目标字符集
                $v = $this->characet($v, 'utf-8');
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }
    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {
        if (!empty($data)) {
            $fileType = 'utf-8';
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }
        return $data;
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
    {//var_dump(123);die;
        $orderInfo =D('chargedetail')->getorder($where);
        //var_dump($orderInfo);die;
        return $orderInfo;
    }


}