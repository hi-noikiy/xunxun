<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\Easemob;
use Think\Exception;
use Think\Log;
/**
 * 官方微信支付的逻辑.
 * Class AliPayAction
 */
class WeiXinController extends BbaseController
{
    protected static $OPEN_WEIXIN = array(); // 微信开放平台,app支付+扫码支付.

    protected static $MP_WEIXIN   = array(); // 微信公众平台，公众号登录.

    public function __construct()
    {
        parent::__construct();// 初始化父类的魔术方法.

        $open_weiXin = C("OPEN_WEIXIN"); //开放平台
        $mp_weiXin   = C("MP_WEIXIN"); //公众平台.
       /* if (empty($open_weiXin)) {
            throw new Exception(L("_MISS_WEIXIN_CONFIG_"));
        }

        if (empty($mp_weiXin)) {
            throw new Exception(L("_MISS_WEIXIN_CONFIG_"));
        }*/

        // 微信开放平台配置(微信app支付).
        self::$OPEN_WEIXIN = $open_weiXin;

        // 微信公众平台配置.(微信公众号支付和登录)
        self::$MP_WEIXIN   = $mp_weiXin;
    }

    /**
     * 微信APP支付接口.
     *
     * @param string $orderNo
     * @param int    $rmb  金额，RMB(本系统单位元，微信单位为分，下面生成微信订单金额需要乘以100倍)
     */
    public function appWeiXin($orderNo, $rmb)
    {
        $result = $this->createWeiXinOrder($orderNo, $rmb*100);
//        unset($result["XML"]);
        if (strcmp($result['RETURN_CODE'], 'FAIL') === 0) {
            E($result['RETURN_MSG'],2004);
        }
//        var_dump($result);die();
        return $this->initPrepayData($result);
    }

     /**
     * 微信支付回调.
     */
    public function weiXinNotify()
    {
        // 获取POST数据.
        $file  = 'log.txt';//要写入文件的文件名（可以是任意文件名），如果文件不存在，将会创建一个
        try{
            $time=date('Y-m-d H:i:s',time());
            $asynResp = file_get_contents("php://input");
            // 解析XML文件.
            $response = pregWeixinData($asynResp);
            $orderNo = $response['out_trade_no'];   // 系统订单号.
            $dealid  = $response['transaction_id']; // 第三方订单号.
            $rmb     = $response['total_fee']/100;  // 微信单位为分，除以100.
            file_put_contents($file,$dealid,FILE_APPEND);
            Log::record("回调状态". json_encode(json_encode($response)), "INFO" );
            file_put_contents("/tmp/Weixin.log","Weixin--".date("Y-m-d H:i:s",time()).":".json_encode($response)."".PHP_EOL,FILE_APPEND);
            //测试数据开始
//            $orderNo='1000064156181722575474';
//            $dealid ='1000064156181722575474';
//            $rmb='0.01';
//            file_put_contents($file,$dealid,FILE_APPEND);
            /*$orderWhere["orderno"] = $orderNo;
            $orderInfo["uid"] = 10060;
            $orderInfo['charge_time'] = "2019-04-26 13:55:37";
            $orderInfo["coin"] = 80;
            D('member')->where(array("id"=>$orderInfo["uid"]))->setInc('grade_coin',$orderInfo["coin"]);
            $update = [
                "charge_time" => $orderInfo['charge_time'],
            ];
            D('member')->where(array("id"=>$orderInfo["uid"]))->save($update);*/
            //测试数据结束
            $content = "微信官方支付";
            // 如果回调逻辑操作全部成功.
            $orderWhere["orderno"] = $orderNo;
            // 如果回调逻辑操作全部成功
            $updateStatus = OrderController::updateOrder($orderNo, $dealid, $content, $rmb);
            Log::record("更改状态". json_encode(json_encode($updateStatus)), "INFO" );
            // 逻辑操作全部成功.
            if ($updateStatus) {
                 // 获取订单信息.
                $where=array("status" => 1, "orderno" => $orderNo);
                $orderInfo = D('chargedetail')->getorder($where);
                //加入流水表(增加m豆)
                $data=array(
                    'uid'=>$orderInfo['uid'],
                    'action'=>"charges",
                    'content'=>'增加M豆',
                    'addtime'=>$time,
                    'status'=>"微信",
                    'bean'=>$orderInfo['coin'],
                );
                // var_dump($data);die;
                $beandetail= D('beandetail')->addDatas($data);
                /*D('member')->where(array("id"=>$orderInfo["uid"]))->setInc('grade_coin',$orderInfo["coin"]);
                $update = [
                    "charge_time" => $orderInfo['charge_time'],
                ];
                D('member')->where(array("id"=>$orderInfo["uid"]))->save($update);*/
                //当前用户是vip会员状态并且是充值虚拟币才能增长经验值,判断当前用户是否购买过vip(当前时间-购买时间大于当前时长长,表示vip,反之)
                VipController::vipupdate($orderInfo['uid'],$orderInfo['type'],$where);
               /* $user_info = D("Member")->user_vipinfo($orderInfo['uid']);
                $end_time = date('Y-m-d H:i:s',time());
                $cnt = strtotime($end_time) - strtotime($user_info['vip_buytime']);
                $cnt = floor($cnt/(3600*24));       //算出天数
                if($orderInfo['type'] == 1 && $cnt<$user_info['long_day']){
                    //根据用户id获取char_unit
                    $type = 1;      //1是充值
                    $vipexp_data = D('VipExp')->detail($type);
                    $exp_admin_values = D('member')->getOneByIdField($orderInfo["uid"],"exp_admin_values");      //取得经验值
                    $rmb = M('chargedetail')->where($where)->getField("rmb");
                    $char_unit = M('member')->where(array("id"=>$orderInfo["uid"]))->getField("char_unit");
                    if(($rmb + $char_unit)%$vipexp_data['exp_nuit'] !== 0){
                        $number = ($rmb + $char_unit)%$vipexp_data['exp_nuit'];      //取余值
//                    var_dump($number);
                        $update = [
                            "char_unit" => $number,      //改变剩余虚拟币
                        ];
                        D('member')->updateDate($orderInfo["uid"],$update);
                        $number_exp_values = floor(($rmb + $char_unit)/$vipexp_data['exp_nuit']);     //求出计算单位的几倍值
                        $update = [
                            "exp_admin_values" => $exp_admin_values + ($number_exp_values*$vipexp_data['exp_values']),       //改变经验值
                        ];
//                    var_dump($update);die();
                        D('member')->updateDate($orderInfo["uid"],$update);
                    }else{
                        $number = ($rmb + $char_unit)%$vipexp_data['exp_nuit'];
                        $number_exp_values = floor(($rmb + $char_unit)/$vipexp_data['exp_nuit']);     //求出计算单位的几倍值
                        $update = [
                            "char_unit" => 0,
                            "exp_admin_values" => $exp_admin_values + ($number_exp_values*$vipexp_data['exp_values']),       //改变经验值
                        ];
                        D('member')->updateDate($orderInfo["uid"],$update);
                    }
                }*/
                $this -> returnCode = 200;
                $this -> returnMsg = "操作成功";
              
            }
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 微信扫码支付
     *
     * @param string  $orderNo 系统订单号.
     * @param integer $rmb     下单金额.
     */
    public function binaryCodeWeiXin($orderNo, $rmb)
    {
        //请求微信，生成微信订单.
        $responseFromWeiXin = $this->createWeiXinOrder($orderNo, $rmb * 100);

        if (strcmp($responseFromWeiXin['return_code'], 'FAIL') === 0) {
            $this->responseError($responseFromWeiXin['return_msg']);
        }

        // 导入第三方二维码类库.
        require_once realpath(__DIR__ .'/../../../').'/Extension/phpqrcode/phpqrcode.php';

        $pngUrl = 'http://meilibo.cxtv.kaduoxq.com';

        if (isset($result['code_url'])) {
            $pngUrl = $result['code_url'];
        }
        // 直接返回二维码.
        Qrcode::png($pngUrl);
    }

    /**
     * 微信授权,微信公众号登录.
     */
    public function weiXinCallback($code = 0, $state = 0) {
        if($code != 0){
            // 获取域名.
            $config = \MClient\Text::inst('api')->setClass('SiteConfig')->getSiteConfig("siteurl", ["id" => 1]);
            $domain = !empty($config['siteurl']) ? $config['siteurl'] : 'http://demo.meilibo.net';

            if (count($arr = explode("|", $state))) {
                $state = $arr[0]; // 操作类型.
                $curRoomNum = $arr[1];
                // 下面数据与代理有关.
                $o_id   = $arr[2]?$arr[2]:1;
                $a_id   = $arr[3]?$arr[3]:1;
                $b_id   = $arr[4]?$arr[4]:1;
                $c_id   = $arr[5]?$arr[5]:1;
                $mobile = $arr[6]?$arr[6]:0;
            }else{
                $curRoomNum = "hall"; // 默认进大厅.
            }
            switch ($state) {
                case "ShareLogin":
                    $appId     = static::$MP_WEIXIN['APPID'];
                    $AppSecret = static::$MP_WEIXIN['APPSECRET'];
                    $data = array(
                        "appid"      => $appId,
                        "secret"     => $AppSecret,
                        "code"       => $code,
                        "grant_type" => "authorization_code"
                    );
                    $url = "https://api.weixin.qq.com/sns/oauth2/access_token";
                    $json = $this->curlRequest($url, false, $data);
                    $json = json_decode($json);
                    file_put_contents(".a.log", 'AAAA'.PHP_EOL, FILE_APPEND);
                    file_put_contents(".a.log", $json.PHP_EOL, FILE_APPEND);
                    $info_data = array(
                        "access_token" => $json->access_token,
                        "openid"       => $json->openid,
                        "lang"         => "zh_CN"
                    );

                    $info_url = "https://api.weixin.qq.com/sns/userinfo";
                    $info = $this->curlRequest($info_url, false, $info_data);
                    $info = json_decode($info);
                    $info->web = 1;//之後區分是分享頁登錄還是APP微信登錄
                    if(isset($_COOKIE['referee_id'])) {
                        $info->referee_id = $_COOKIE['referee_id'];
                    }
                    $info = json_encode($info);
                    if(!empty($c_id)&&$c_id !=1){
                        $login_data = array(
                            "openid"  => $json->openid,
                            "type"    => "wechat",
                            "payload" => $info,
                            "param"   => json_encode(array("o_id"=>$o_id,"a_id"=>$a_id,"b_id"=>$b_id,"c_id"=>$c_id,"mobile"=>$mobile))
                        );
                    }else{
                        $login_data = array(
                            "openid"  => $json->openid,
                            "type"    => "wechat",
                            "payload" => $info
                        );
                    }
                    $login_url = $domain."/OpenAPI/V1/Auth/login";
                    $userInfo = $this->curlRequest($login_url, true, $login_data);
                    $userInfo = json_decode($userInfo, true);
                    file_put_contents(".a.log", 'BBBB'.PHP_EOL, FILE_APPEND);
                    file_put_contents(".a.log", $userInfo.PHP_EOL, FILE_APPEND);
                    $_SESSION['uid'] = $userInfo['data']['id'];
                    $_SESSION['token'] = $userInfo['data']['token'];
                    //微信大厅登录
                    if ($curRoomNum == 'hall') {
                        $back_url = $domain."/app/wx_index?uid=".$userInfo['data']['id'];
                        echo "<script>window.location.href='".$back_url."'</script>";
                        break;
                    }
                    $back_url = $domain."/app/share";
                    echo "<script>window.location.href='".$back_url."?current_room=".$curRoomNum."'</script>";
                    break;

                case "login": //如果为绑定公众号登录
                    file_put_contents("/tmp/wx.log","code:".$code);
                    $appid = static::$MP_WEIXIN['APPID'];
                    $AppSecret = static::$MP_WEIXIN['APPSECRET'];
                    $data = array(
                        "appid" => $appid,
                        "secret" => $AppSecret,
                        "code" => $code,
                        "grant_type" => "authorization_code"
                    );
                    $url = "https://api.weixin.qq.com/sns/oauth2/access_token";
                    $json = $this->curlRequest($url, false, $data);
                    file_put_contents("/tmp/wx.log","access_token:".$json.PHP_EOL,FILE_APPEND);
                    $json = json_decode($json);
                    $info_data = array(
                        "access_token" => $json->access_token,
                        "openid" => $json->openid,
                        "lang" => "zh_CN"
                    );

                    $info_url = "https://api.weixin.qq.com/sns/userinfo";
                    $info = $this->curlRequest($info_url, false, $info_data);
                    file_put_contents("/tmp/wx.log","info:".$info.PHP_EOL,FILE_APPEND);
                    $info = json_decode($info);
                    $unionid = $info->unionid;
                    $userWeiXinExists =  D('Member')->where(array('wxunionid' => $unionid))->field("id, beanbalance,agentuid")->find();
                    $userQQExists =  D('Member')->where(array('wxunionid' => $unionid."_qq"))->field("id, beanbalance,agentuid")->find();
                    $userMobileExists =  D('Member')->where(array('wxunionid' => $unionid."_mobile"))->field("id, beanbalance,agentuid")->find();
                    // 如果没有任何一种登录方式绑定过微信.
                    if (empty($userWeiXinExists) && empty($userQQExists) && empty($userMobileExists)) {
                        echo "您的微信还未绑定寻寻直播账号，打开寻寻直播的app-我的主页-收益-进行微信授权绑定";exit();
                    }
                    //如果已经绑定过微信，有wxunionid,绑定openid
                    $info = json_decode($info);
                    $openid = $json->openid;
                    $uidArr = array();
                    $typeArr = array();
                    //如果为手机号注册的用户
                    if (!empty($userMobileExists)) {
                        $unionid = $unionid."_mobile";
                        $uidArr[] = $userMobileExists["id"];
                        if ($userMobileExists["agentuid"] !=0){
                            $typeArr[] = "agent";
                        } else {
                            $typeArr[] = "normal";
                        }
                        D('Member')->where(array('wxunionid' => $unionid))->save(array("wxopenid"=>$openid));
                    }
                    //QQ
                    if (!empty($userQQExists)) {
                        $unionid = $unionid."_qq";
                        $uidArr[] = $userQQExists["id"];
                        if ($userQQExists["agentuid"] !=0){
                            $typeArr[] = "agent";
                        } else {
                            $typeArr[] = "normal";
                        }
                        D('Member')->where(array('wxunionid' => $unionid))->save(array("wxopenid"=>$openid));
                    }
                    // 微信
                    if (!empty($userWeiXinExists)) {
                        $uidArr[] = $userWeiXinExists["id"];
                        if ($userWeiXinExists["agentuid"] !=0){
                            $typeArr[] = "agent";
                        } else {
                            $typeArr[] = "normal";
                        }
                        D('Member')->where(array('wxunionid' => $unionid))->save(array("wxopenid"=>$openid));
                    }
                    $_SESSION['uidArr'] = $uidArr;
                    $_SESSION['check_time'] = time();
                    $_SESSION["typeArr"] = $typeArr;
                    $url = $domain."/app/personInfo";
                    // 绑定成功以后. 跳转到个人余额页面.
                    echo "<script>window.location.href='".$url."'</script>";
            }
        } else {
            $this->responseError(L('_DATA_TYPE_INVALID_'));
        }

    }

    /**
     * 向微信请求生成订单.
     *
     * @param string   $orderNo 订单号.
     * @param integer $rmb      金额.
     *
     * @return array
     */
    private function createWeiXinOrder($orderNo, $rmb)
    {

        $xml = $this->initOrderData($orderNo ,$rmb);
//        var_dump($xml);exit;
        $response = $this->postXmlCurl($xml);
        $result = $this->xmlToArr($response);
        return $result;
    }

    /**
     * 初始化微信订单请求数据.
     */
    private function initOrderData($out_trade_no, $total_free)
    {
        $nonce_str = $this->getRandomStr();
        $param = array(
            'appid'            => static::$OPEN_WEIXIN['APPID'],
            'body'             => "M豆",
            //'detail'           => L('_BUY_COIN_'),
            'fee_type'         => 'CNY',
            'mch_id'           => static::$OPEN_WEIXIN['MCHID'],
            'nonce_str'        => $nonce_str,
            'notify_url'       => static::$OPEN_WEIXIN['NOTIFY_URL'],
//            'notify_url'       => C('NOTIFY_URL'),
            'out_trade_no'     => $out_trade_no,
            'spbill_create_ip' => $_SERVER["REMOTE_ADDR"],
            'time_expire'      => date("YmdHms",strtotime("+2 hours")),
            'time_start'       => date("YmdHms"),
            'total_fee'        => $total_free,
            'trade_type'       => 'APP',
        );
//        var_dump($param);die;
        $str = $this->arrayToKeyValueString($param);
        $param['sign'] = $this->getSign($str);
        return $this->arrToXML($param);
    }

    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws WxPayException
     *
     * @return string
     *								----------------------------
     */
    private static function postXmlCurl($xml, $useCert = false, $second = 30)
    {
//        echo $xml;
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch,CURLOPT_URL, static::$OPEN_WEIXIN['PLACE_ORDER']);
//        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
//        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        //设置header
//        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
       // var_dump($xml);die;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
      // var_dump($xml);die;
        $data = curl_exec($ch);
//        echo 123;
//        var_dump($data);die;
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            // throw new Exception("curl出错，错误码:$error");
            return "<xml><return_code>FAIL</return_code><return_msg>"."系统不支持"."</return_msg></xml>";
        }
    }

    /**
     * XML转数组
     * 数组格式 array('大写xml的tag'	=>	'xml的value');
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

    /**
     * 生成随机字符串
     * @return string
     */
    private function getRandomStr()
    {
        return md5('meilibo' . microtime() . 'weixin' . rand(100,9999));
    }

    /**
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

    /**
     * 获取签名.
     * @param string $str 请求参数组成的字符串.
     *
     * @return string 加密后的字符串.
     */
    private function getSign($str)
    {
        $str = $this->joinApiKey($str);
        return strtoupper(md5($str));
    }

    /**
     * 拼接API密钥
     * @param string $str 请求的字符串
     *
     * @return string 拼接商户平台的apikey以后的字符串.
     */
    private function joinApiKey($str)
    {
        return $str . "key=".static::$OPEN_WEIXIN['APIKEY'];
    }

    /**
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

    /**
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
        //var_dump($appData);die;
        //20190814 微信客户端报错兼容字段，更新版本后以下四个字段可以删除
        $appData['partnerId'] =  $appData['partnerid'];
        $appData['prepayId'] =  $appData['prepayid'];
        $appData['nonceStr'] =  $appData['noncestr'];
        $appData['timeStamp'] =  $appData['timestamp'];
        return $appData;
    }

}
