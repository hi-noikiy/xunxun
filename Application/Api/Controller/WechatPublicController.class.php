<?php
namespace Api\Controller;
use Api\Service\MemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\Easemob;
use Think\Exception;
use Think\Log;
class WechatPublicController extends Controller{
    protected static $list ; // 充值比例
    protected static $WECHAT_OPEN = array(); // 微信公众号.

    public function __construct(){
        parent::__construct();// 初始化父类的魔术方法.
        $wechat_open = C("WECHAT_OPEN"); //开放平台
        // 微信开放平台配置(微信app支付).
        self::$WECHAT_OPEN = $wechat_open;
        // 获取支付比例.
        self::$list =D('charge')->getChargeList();
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
    public function publicPay()
    {
        $totalFee = $_REQUEST['rmb'];
        if (!session_id()) session_start();        
        $selfuid = $_SESSION['selfuid'];
        $openid = $_SESSION['wxopid'];
        // $outTradeNo = $this->createOrderNo($selfuid);
        $orderName = "微信公众号代充";
        $notifyUrl = static::$WECHAT_OPEN['NOTIFY_URL'];
        $timestamp = time();

        $config = array(
            'mch_id' => static::$WECHAT_OPEN['MCHID'],
            'appid' => static::$WECHAT_OPEN['APPID'],
            'key' => static::$WECHAT_OPEN['APIKEY'],
        );
        

        $coin = self::$list[$totalFee]?self::$list[$totalFee]["diamond"]:0;
        if ($coin == 0) {
            E('支付比例错误',2003);
        }
        // 创建订单.
        $outTradeNo = $this->createOrder($selfuid,$totalFee,$coin);
        if (!$outTradeNo) {
            Log::record("创建订单失败----uid=". $uid.'---'.'微信公众号代充'.'---'.$totalFee, "INFO" );
            E('创建订单失败',2003);
        }




        //$orderName = iconv('GBK','UTF-8',$orderName);
        $unified = array(
            'appid' => $config['appid'],
            'attach' => 'pay',             //商家数据包，原样返回，如果填写中文，请注意转换为utf-8
            'body' => $orderName,
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::createNonceStr(),
            'notify_url' => $notifyUrl,
            'openid' => $openid,            //rade_type=JSAPI，此参数必传
            'out_trade_no' => $outTradeNo,
            'spbill_create_ip' => '127.0.0.1',
            'total_fee' => intval($totalFee * 100),       //单位 转为分
            'trade_type' => 'JSAPI',
        );
        $unified['sign'] = self::getSign($unified, $config['key']);
        Log::record("公众号发送订单参数".json_encode($unified), "INFO" );
        $responseXml = self::curlPost('https://api.mch.weixin.qq.com/pay/unifiedorder', self::arrayToXml($unified));
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);     
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);  
        if ($unifiedOrder === false) {
            Log::record("公众号订单失败返回参数".json_encode($unifiedOrder), "INFO" );
            echo '500';
            die('订单创建失败');
        }
        if ($unifiedOrder->return_code != 'SUCCESS') {
            Log::record("公众号订单失败返回参数".json_encode($unifiedOrder), "INFO" );
            echo '500';
            // die($unifiedOrder->return_msg);
            die('您的订单创建失败');
        }
        // if ($unifiedOrder->result_code != 'SUCCESS') {
        //     Log::record("公众号订单返回参数".json_encode($unifiedOrder), "INFO" );
        //     echo '500';
        //     die($unifiedOrder->err_code);
        // }

        $arr = array(
            "appId" => $config['appid'],
            "timeStamp" => "$timestamp",        //这里是字符串的时间戳，不是int，所以需加引号
            "nonceStr" => self::createNonceStr(),
            "package" => "prepay_id=" . $unifiedOrder->prepay_id,
            "signType" => 'MD5',
        );
        $arr['paySign'] = self::getSign($arr, $config['key']); 
        Log::record("公众号订单成功返回参数".json_encode($arr), "INFO" ); 
        echo json_encode($arr);     
        // return $arr;
    }


    /*
    * 充值调用接口
    */

    public function publicPay1($uid, $rmb){

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
            if (!$orderNo) {
                Log::record("创建订单失败----uid=". $uid.'---'.'微信公众号代充'.'---'.$rmb, "INFO" );
                E('创建订单失败',2003);
            }
            $data = $this->createWeiXinOrder($orderNo,$rmb*100);
            echo json_encode($data);
        }catch(Exception $e){
            echo json_encode($e);
        }
    }

    /*
    * 回调数据
    */
    public function weiXinBack(){
        try{
            $time=date('Y-m-d H:i:s',time());
            $Resp = file_get_contents("php://input");
            // 解析XML文件.
            $response = pregWeixinData($Resp);
            $orderNo = $response['out_trade_no'];   // 系统订单号.
            $dealid  = $response['transaction_id']; // 第三方订单号.
            $rmb     = $response['total_fee']/100;  // 微信单位为分，除以100.
            Log::record("公众号回调状态". json_encode($response), "INFO" );
            file_put_contents("/tmp/Weixin.log","Weixin--".date("Y-m-d H:i:s",time()).":".json_encode($response)."".PHP_EOL,FILE_APPEND);
            // 如果回调逻辑操作全部成功.
            // $orderWhere["orderno"] = $orderNo;
            // 如果回调逻辑操作全部成功
            $updateStatus = $this->updateOrder($orderNo,$rmb,$response);
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
               // $vip =  VipController::vipupdate($orderInfo['uid'],$orderInfo['type'],$where);
               
                // $chargecoin = M('member')->where(array("id"=>$orderInfo['uid']))->setInc('chargecoin',$orderInfo["coin"]);
                // $deng_chargecoin = floor(MemberService::getInstance()->getOneByIdField($orderInfo['uid'],"chargecoin"));         //当前充值的虚拟币
                // $lv_dengji = lv_dengji($deng_chargecoin);     //获取等级
                // $result_dengji = array('lv_dengji'=>$lv_dengji);        //修改的字段值
                // $lvdengji = D('member')->updateDate($orderInfo['uid'],$result_dengji);        //修改等级

                if($beandetail){
                    echo '<xml>
                      <return_code><![CDATA[SUCCESS]]></return_code>
                      <return_msg><![CDATA[OK]]></return_msg>
                    </xml>';
                    return json_encode(['code'=>200,'msg'=>'充值成功']);
                }else{
                    Log::record("公众号回调改订单beandetail-----". json_encode($data), "INFO" );
                    E('修改回调失败',2005);
                }
            }

        }catch(Exception $e){
            Log::record("公众号回调异常-----". json_encode($e), "INFO" );
            // echo json_encode($e);
        }
    }


    /*
     * 查询订单接口
     */
    public  function updateOrder($orderNo,$rmb,$response){
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
        $dataes['dealid']  = $response['transaction_id'];
        $updatestatus=$model->where(array("status"=>0,"orderno"=>$orderNo))->save($dataes);
        Log::record("订单状态". json_encode(json_encode($updatestatus)), "INFO" );
        // 3. 给用户加值.
        $increcoin= D('member')->where(array("id"=>$orderInfo["uid"]))->setInc('totalcoin',$orderInfo["coin"]);
        $chargecoin= D('member')->where(array("id"=>$orderInfo["uid"]))->setInc('chargecoin',$orderInfo["coin"]);
        //等级修改
        $totalcoin = floor(MemberService::getInstance()->getOneByIdField($orderInfo['uid'],"totalcoin"));       //获取此用户的充值
        $lv_dengji = lv_dengji($totalcoin);     //获取等级
        $data_dengji = array('lv_dengji'=>$lv_dengji);        //修改的字段值
        $result = D('member')->updateDate($orderInfo['uid'],$data_dengji);        //修改等级
        if ($updatestatus && $increcoin && $chargecoin) {
            RedisCache::getInstance()->getRedis()->HINCRBY('userinfo_'.$orderInfo['uid'], 'totalcoin',$orderInfo["coin"]);
            RedisCache::getInstance()->getRedis()->hset('userinfo_'.$orderInfo['uid'], 'lv_dengji',$lv_dengji);
            $model->commit();
            return true;
        } else {
            $model->rollback();
            return false;

        }
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
    /*
     * 微信公众号代充接口.
     *
     * @param string $orderNo
     * @param int    $rmb  金额，RMB(本系统单位元，微信单位为分，下面生成微信订单金额需要乘以100倍)
     */
    public function publicNumber($orderNo, $rmb)
    {
        $result = $this->createWeiXinOrder($orderNo, $rmb*100);
        if (strcmp($result['RETURN_CODE'], 'FAIL') === 0) {
            E($result['RETURN_MSG'],2004);
        }
        return $this->initPrepayData($result);

    }

    /*
     * 向微信请求生成订单.
     *
     * @param string   $orderNo 订单号.
     * @param integer $rmb      金额.
     *
     * @return array
     */
    private function createWeiXinOrder($orderNo, $rmb){
        $xml = $this->initOrderData($orderNo ,$rmb);
        $response = $this->postXmlCurl($xml);
        $result = $this->xmlToArr($response);
        return $result;
    }

    /*
     * 初始化微信订单请求数据.
     */
    private function initOrderData($out_trade_no,$total_fee){
        $nonce_str = $this->getRandomStr();
        $param = array(
            'appid'            => static::$WECHAT_OPEN['APPID'],
            'body'             => "M豆",
            'fee_type'         => 'CNY',
            'mch_id'           => static::$WECHAT_OPEN['MCHID'],
            'nonce_str'        => $nonce_str,
            'notify_url'       => static::$WECHAT_OPEN['NOTIFY_URL'],
            'out_trade_no'     => $out_trade_no,
            'scene_info'       =>'{"h5_info": {"type":"Wap","wap_url": "http://www.muayy.com/": "公众号代充"}}',
            'spbill_create_ip' => $_SERVER["REMOTE_ADDR"],
            'time_expire'      => date("YmdHms",strtotime("+2 hours")),
            'time_start'       => date("YmdHms"),
            'total_fee'        => $total_fee,
            'trade_type'       => 'MWEB',

        );
        $str = $this->arrayToKeyValueString($param);
        $param['sign'] = $this->getSign($str);
        return  $this->arrToXML($param);
    }

    /*
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws WxPayException
     *
     * @return string
     *                              ----------------------------
     */
    private static function postXmlCurl($xml, $useCert = false, $second = 30)
    {
//        echo $xml;die;
//        var_dump(static::$WECHAT_OPEN['PLACE_ORDER']);die;
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch,CURLOPT_URL, static::$WECHAT_OPEN['PLACE_ORDER']);
//        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
//        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        //设置header
//        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
//        echo 123;
//        var_dump($data);die;
        //返回结果
        if($data){
            curl_close($ch);
//            var_dump($data);die;
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            // throw new Exception("curl出错，错误码:$error");
            return "<xml><return_code>FAIL</return_code><return_msg>"."系统不支持"."</return_msg></xml>";
        }
    }
    /*
     * 生成随机字符串
     * @return string
     */
    private function getRandomStr()
    {
        return md5('meilibo' . microtime() . 'weixin' . rand(100,9999));
    }

    /*
     * @param array $param 请求微信的参数数组
     * @return string
     */
    private function arrayToKeyValueString($param)
    {
        $str = '';
        foreach($param as $key => $value) {
            $str = $str . $key .'=' . $value . '&';
        }
        return $str;
    }
    /*
     * 获取签名.
     * @param string $str 请求参数组成的字符串.
     *
     * @return string 加密后的字符串.
     */
    private function getSign2($str)
    {
        $str = $this->joinApiKey($str);
        return strtoupper(md5($str));
    }

    /*
     * 拼接API密钥
     * @param string $str 请求的字符串
     *
     * @return string 拼接商户平台的apikey以后的字符串.
     */
    private function joinApiKey($str)
    {
        return $str . "key=".static::$WECHAT_OPEN['APIKEY'];
    }

    /*
     * 数组转XML
     */
    private function arrToXML($param, $cdata = false)
    {
        $xml = "<xml>";
        $cdataPrefix = $cdataSuffix = '';
        if ($cdata) {
            $cdataPrefix = '<![CDATA[';
            $cdataSuffix = ']]>';
        }

        foreach($param as $key => $value) {
            $xml .= "<{$key}>{$cdataPrefix}{$value}{$cdataSuffix}</$key>";
        }
        $xml .= "</xml>";
        // var_dump($xml);die;
        return $xml;
    }
    /*
    * XML转数组
    * 数组格式 array('大写xml的tag'    =>  'xml的value');
    * 数组所有键为大写！！！-----重要！
    */
    private function xmlToArr($xml)
    {
        //禁止引用外部xml实体
        /*libxml_disable_entity_loader(true);
        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $val = json_decode(json_encode($xmlstring),true);
        return $val;*/
        $parser = xml_parser_create();
        xml_parse_into_struct($parser, $xml, $data, $index);
        $arr = array();
        foreach ($data as $key => $value) {
//            var_dump($value);
            $arr[$value['tag']] = $value['value'];
        }
        return $arr;
    }

    /*
     * 调用支付接口.
     *
     * @param array $prepayData 微信请求生成订单时返回的数据.
     *
     * @return array 用户客户端调用微信的支付接口的函数.
     */
    private function initPrepayData($prepayData)
    {
        $appData = array(
            'appid'     => $prepayData['APPID'],
            'partnerid' => $prepayData['MCH_ID'],
            'prepayid'  => $prepayData['PREPAY_ID'],
            'package'   => 'Sign=WXPay',
            'noncestr'  => $this->getRandomStr(),
            'timestamp' => time()."",
        );

        ksort($appData);
        $str = $this->arrayToKeyValueString($appData);
        $appData['sign'] = $this->getSign($str);
        return $appData;
    }





    //
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
