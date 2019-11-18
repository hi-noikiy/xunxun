<?php
namespace Home\Controller;
use Api\Service\MemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\Easemob;
use Think\Exception;
use Think\Log;
class AlishhController extends Controller{

	protected static $WECHAT_OPEN = array(); // 微信公众号.
    public function __construct(){
        parent::__construct();// 初始化父类的魔术方法.
        $wechat_open = C("WECHAT_OPEN"); //开放平台
        // 微信开放平台配置(微信app支付).
        self::$WECHAT_OPEN = $wechat_open;
    }
	//未登录充值界面
    public function paySelect(){
  //   	if (!session_id()) session_start();
		// $id = $_SESSION['selfuid'];
		// if ($id) {
		// 	$this->redirect('/index.php/home/alishh/userPay');
		// }else{
			$charge = D('Api/Charge');
			$result = $charge->getCharge();
			$this->assign('result',$result);
	        $this->display();
		// }
		
    }

	//登录界面
	public function addUser(){
	    return $this->display();
	}
	//已登录充值界面
	public function userPay(){
		if (!session_id()) session_start();
		$id = $_SESSION['selfuid'];
		if($id){
			// $id = $_REQUEST['id'];
			$member = D('Api/Member');
			$user = $member->detailUser($id);
			if($user['avatar'] == ""){
				$user['avatar'] = C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
			}else{
				$user['avatar'] = C('APP_URL_image').'/'.$user['avatar'];
			}
			// var_dump($user);die;
			$charge = D('Api/Charge');
			$result = $charge->getCharge();
			$this->assign('result',$result);
			$this->assign('user',$user);
			$this->display();
		}else{
			$this->redirect('/index.php/home/alishh/addUser');
		}
		
	    
	}
	//检查ID是否存在
	public function isUser(){
		if($_POST){
			$id = $_REQUEST['id'];
			$member = D('Api/Member');
			$result = $member->getOneByprettyId($id);
			if($result){
				if (!session_id()) session_start();
				$_SESSION['selfuid'] = $result['id'];

				echo json_encode(['code'=>200,'data'=>$result]);
			}else{
				echo json_encode(['code'=>201,'msg'=>'用户不存在，请重新输入']);
			}
		}else{
			echo json_encode(['code'=>201,'msg'=>'参数错误，请重新输入']);
		}
	}


	/**
     * 通过跳转获取用户的openid，跳转流程如下：
     * 1、设置自己需要调回的url及其其他参数，跳转到微信服务器https://open.weixin.qq.com/connect/oauth2/authorize
     * 2、微信服务处理完成之后会跳转回用户redirect_uri地址，此时会带上一些参数，如：code
     * @return 用户的openid
     */
    public function gzhIndex()
    {
        //通过code获得openid
        if (!isset($_GET['code'])){
            //触发微信返回code码
            $scheme = $_SERVER['HTTPS']=='on' ? 'https://' : 'http://';
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
			if (!session_id()) session_start();
               $_SESSION['wxopid'] = $openid;            

			$this->redirect('/index.php/home/alishh/paySelect');
            // return $openid;
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
        $urlObj["appid"] = static::$WECHAT_OPEN['APPID'];
        $urlObj["secret"] = static::$WECHAT_OPEN['APPSECRET'];
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
        $urlObj["appid"] = static::$WECHAT_OPEN['APPID'];
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
