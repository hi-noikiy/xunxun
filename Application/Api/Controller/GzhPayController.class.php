<?php
namespace Api\Controller;

use Think\Controller;
use Common\Util\RedisCache;
use Think\Exception;
use Think\Log;

class GzhPayController extends BaseController
{
    protected static $list ; // 充值比例
    protected $mchid;
    protected $appid;
    protected $appKey;
    protected $apiKey;
    public $data = null;
    public function _initialize()
    {
    	$WECHAT_OPEN = C("WECHAT_OPEN");
        $this->mchid = $WECHAT_OPEN['MCHID']; 
        $this->appid = $WECHAT_OPEN['APPID']; 
        $this->appKey = $WECHAT_OPEN['APPSECRET']; 
        $this->apiKey = $WECHAT_OPEN['APIKEY'];  
        // 获取支付比例.
        self::$list =D('charge')->getChargeList();
    }


    /*
    * 充值调用接口
    */
    public function publicPay($uid, $rmb){
        try{
            $uid = $_REQUEST['uid'];
            $rmb = $_REQUEST['rmb'];
            if(!isset($uid) || !isset($rmb)  || $rmb < 1){
                E('参数不全',2000);
            }
            $coin = self::$list[$rmb]?self::$list[$rmb]["diamond"]:0;
            if ($coin == 0) {
                E('支付比例错误',2003);
            }
            // 创建订单.
            $orderNo = $this->createOrder($uid,$rmb,$coin);
            Log::record("公众号创建订单uid=". $uid.'---'.'微信公众号代充'.'---'.$rmb, "INFO" );
            if (!$orderNo) {
                E('创建订单失败',2003);
            }
            
            $openId = $_SESSION['gzhopid'];
            $notifyUrl = $wechat_open['NOTIFY_URL'];
            $time = time();
            $data = $this->createJsBizPackage($openId,$rmb*100,$orderNo,"M豆",$notifyUrl,$time);
            echo json_encode($data);
        }catch(Exception $e){
            echo json_encode($e);
        }
    }

    /*
     * 生成订单号
     */
    private function creategzhOrder($uid, $rmb, $coin){
        //生成订单号
        $orderNo = $this->createOrderNo($uid);
        $data = [
            'uid'      => $uid,
            'rmb'      => $rmb,
            'coin'     => $coin,
            'content'  => '微信公众号代充',
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
        Log::record("公众号创建订单".'---'.json_encode($data), "INFO" );
        // 创建订单成功，返回订单号.
        if ($createOrderSuccessOrFail) {
            return $orderNo;
        } else {
            return false;
        }

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
    * 回调数据
    */
    public function gzhBack(){
        try{
            $time=date('Y-m-d H:i:s',time());
            $Resp = file_get_contents("php://input");
            // 解析XML文件.
            $response = pregWeixinData($Resp);
            $orderNo = $response['out_trade_no'];   // 系统订单号.
            $dealid  = $response['transaction_id']; // 第三方订单号.
            $rmb     = $response['total_fee']/100;  // 微信单位为分，除以100.
            Log::record("公众号回调状态". json_encode($response), "INFO" );
            file_put_contents("/tmp/Weixingzh.log","Weixin--".date("Y-m-d H:i:s",time()).":".json_encode($response)."".PHP_EOL,FILE_APPEND);
            // 如果回调逻辑操作全部成功.
            $orderWhere["orderno"] = $orderNo;
            // 如果回调逻辑操作全部成功
            $updateStatus = $this->updateOrder($orderNo,$rmb);
            // 逻辑操作全部成功.
            if ($updateStatus == true ) {
                // 获取订单信息.
                $where=array("status" => 1, "orderno" => $orderNo);
                $orderInfo = D('chargedetail')->getorder($where);
                if(empty($orderInfo)){
                    E('未查到订单信息',2004);
                }
                //加入流水表(增加m豆)
                $data=array(
                    'uid'=>$orderInfo['uid'],
                    'action'=>"charges",
                    'content'=>'增加M豆',
                    'addtime'=>$time,
                    'status'=>"微信公众号",
                    'bean'=>$orderInfo['coin'],
                );
                $beandetail= D('beandetail')->addDatas($data);
                //当前用户是vip会员状态并且是充值虚拟币才能增长经验值,判断当前用户是否购买过vip(当前时间-购买时间大于当前时长长,表示vip,反之)
               $vip =  VipController::vipupdate($orderInfo['uid'],$orderInfo['type'],$where);
                if($beandetail && $vip){
                    return json_encode(['code'=>200,'msg'=>'充值成功']);
                }else{
                    E('修改回调失败',2005);
                }
            }
        }catch(Exception $e){
            echo json_encode($e);
        }
    }

    /*
     * 更新订单状态
     */
    public  function updateOrder($orderNo){
        // 获取订单信息.
        $where=array("status" => 0, "orderno" => $orderNo);
        $orderInfo = D('chargedetail')->getorder($where);
        if (empty($orderInfo)) {
            return false;
        }
        // 开启事物.
        $model = D('chargedetail');
        $model->startTrans();
        // 2.更新订单状态.
        $dataes['status'] = 1;
        $updatestatus=$model->where(array("status"=>0,"orderno"=>$orderNo))->save($dataes);
        Log::record("订单状态". json_encode(json_encode($updatestatus)), "INFO" );
        // 3. 给用户加值.
        $increcoin= D('member')->where(array("id"=>$orderInfo["uid"]))->setInc('totalcoin',$orderInfo["coin"]);
        //等级修改
        $totalcoin = floor(MemberService::getInstance()->getOneByIdField($orderInfo['uid'],"totalcoin"));       //获取此用户的充值
        $lv_dengji = lv_dengji($totalcoin);     //获取等级
        $data_dengji = array('lv_dengji'=>$lv_dengji);        //修改的字段值
        $result = D('member')->updateDate($orderInfo['uid'],$data_dengji);        //修改等级
        if ($updatestatus && $increcoin) {
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;
        }
    }

    /**
     * 通过跳转获取用户的openid
     * 设置自己需要调回的url及其其他参数，跳转到微信服务器https://open.weixin.qq.com/connect/oauth2/authorize
     * @return 用户的openid
     */
    public function gzhindex()
    {
        //通过code获得openid
        if (!isset($_GET['code'])){
            //触发微信返回code码
            $scheme = array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS']=='on' ? 'https://' : 'http://';
			$uri = $_SERVER['PHP_SELF'].$_SERVER['QUERY_STRING'];
			if($_SERVER['REQUEST_URI']) $uri = $_SERVER['REQUEST_URI'];
            $baseUrl = urlencode($scheme.$_SERVER['HTTP_HOST'].$uri);
            $url = $this->__CreateOauthUrlForCode($baseUrl);
            Header("Location: $url");
            exit();
        } else {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $openid = $this->getOpenidFromMp($code);
            session_start();
            $_SESSION['gzhopid'] = $openid;
            $indexurl = "http://mtestapi.57xun.com/index.php/home/wechatPublic/paySelect";
            Header("Location: $indexurl");
            exit();
        }
    }

    /**
     * 通过code从工作平台获取openid机器access_token
     * @param string $code 微信跳转回来带上的code
     * @return openid
     */
    public function GetOpenidFromMp($code)
    {
        $url = $this->__CreateOauthUrlForOpenid($code);
        $res = self::curlGet($url);
        //取出openid
        $data = json_decode($res,true);
        $this->data = $data;
        $openid = $data['openid'];
        return $openid;
    }

    /**
     * 构造获取open和access_toke的url地址
     * @param string $code，微信跳转带回的code
     * @return 请求的url
     */
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->appid;
        $urlObj["secret"] = $this->appKey;
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
    }

    /**
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = $this->appid;
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_base";
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
    }

    /**
     * 拼接签名字符串
     * @param array $urlObj
     * @return 返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        $buff = "";
        foreach ($urlObj as $k => $v)
        {
            if($k != "sign") $buff .= $k . "=" . $v . "&";
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 统一下单
     * @param string $openid 调用【网页授权获取用户信息】接口获取到用户在该公众号下的Openid
     * @param float $totalFee 收款总费用 单位元
     * @param string $outTradeNo 唯一的订单号
     * @param string $orderName 订单名称
     * @param string $notifyUrl 支付结果通知url 不要有问号
     * @param string $timestamp 支付时间
     * @return string
     */
    public function createJsBizPackage($openid, $totalFee, $outTradeNo, $orderName, $notifyUrl, $timestamp)
    {
        $config = array(
            'mch_id' => $this->mchid,
            'appid' => $this->appid,
            'key' => $this->apiKey,
        );
        //$orderName = iconv('GBK','UTF-8',$orderName);
        $unified = array(
            'appid' => $config['appid'],
            'attach' => 'pay',             
            'body' => $orderName,
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::createNonceStr(),
            'notify_url' => $notifyUrl,
            'openid' => $openid,            
            'out_trade_no' => $outTradeNo,
            'spbill_create_ip' => '127.0.0.1',
            'total_fee' => intval($totalFee * 100),      
            'trade_type' => 'JSAPI',
        );
        $unified['sign'] = self::getSign($unified, $config['key']);
        $responseXml = self::curlPost('https://api.mch.weixin.qq.com/pay/unifiedorder', self::arrayToXml($unified));
		//禁止引用外部xml实体
		libxml_disable_entity_loader(true);	    
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($unifiedOrder === false) {
            die('parse xml error');
        }
        if ($unifiedOrder->return_code != 'SUCCESS') {
            die($unifiedOrder->return_msg);
        }
        if ($unifiedOrder->result_code != 'SUCCESS') {
            die($unifiedOrder->err_code);
        }
        $arr = array(
            "appId" => $config['appid'],
            "timeStamp" => "$timestamp",       
            "nonceStr" => self::createNonceStr(),
            "package" => "prepay_id=" . $unifiedOrder->prepay_id,
            "signType" => 'MD5',
        );
        $arr['paySign'] = self::getSign($arr, $config['key']);
        return $arr;
    }

    public static function curlGet($url = '', $options = array())
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public static function curlPost($url = '', $postData = '', $options = array())
    {
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public static function createNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    public static function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        return $signStr;
    }

    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
    

}
