<?php
namespace Api\Controller;
use Api\Service\MemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\Easemob;
use Think\Exception;
use Think\Log;
class WechatPayController extends BaseController {

	public static $WechatPayConfig;


	/**
	 * 充值比例
	 */
	protected static $list ;


	public function __construct()
	{
		parent::__construct();

		self::$list = M("charge")->order("chargeid asc")->getField('rmb, diamond,present');

		static::$WechatPayConfig = [
			'NOTIFY_URL' => 'http://www.baidu.com',
			'MCHID' => '755437000006',
			'APPID' => 'wx83f69b45802c66f4',
			'APIKEY'=> '7daa4babae15ae17eee90c9e',
			'PLACE_ORDER' => 'https://pay.swiftpass.cn/pay/gateway',
		];
	}

	/**
	 * 微信APP支付接口
	 *
	 * @param string $token
	 * @param int    $num  金额，RMB
	 */
	public function appWeixin($token, $num)
	{

		// $num = 1;
		// $num *= 100;
		$userInfo = TokenHelper::getInstance()->get($token);
		$result = $this->createWixinOrder($this->createOrder($userInfo['uid'], $num), $num * 100);

		if (strcmp($result['RETURN_CODE'], 'FAIL') === 0) {

			$this->responseError($result['RETURN_MSG']);
		}

		$this->responseSuccess(array("token_id"=>$result['TOKEN_ID']));
	}


	/**
	 * 向微信请求订单生成API
	 *
	 * @param int $orderNo
	 * @param int $num
	 */
	public function createWixinOrder($orderNo, $num)
	{
		$xml = $this->initOrderData($orderNo ,$num);
		$response = $this->postXmlCurl($xml);
		$result = $this->xmlToArr($response);

		return $result;
	}

	/**
	 * 统一下单接口
	 *
	 * @param int    $num  金额，RMB
	 * @param int    $type 支付平台 0:支付宝 1:微信
	 */
	protected function createOrder($uid, $num, $type = 0)
	{

		$orderTime = time();

		$orderNo = "{$uid}{$uid}".time().rand(99,99999);
		$coin = !isset(self::$list[$num]) ? 0 : self::$list[$num]["diamond"]+self::$list[$num]["present"];


		if (!( $id = OrderAction::createConsumerOrder($uid, $orderNo, $orderTime, $num, $coin, $type))) {
			$this->responseError(L('_CREATE_ORDER_FAILED_'));
		}

		return $orderNo;
	}

	//微信开放平台支付订单(app 内)
	protected function initOrderData($out_trade_no, $total_free)
	{
		$param = array(
			'appid'=> static::$WechatPayConfig['APPID'],
			'mch_id'=> static::$WechatPayConfig['MCHID'],

			'service' => 'unified.trade.pay',
			'out_trade_no'=> "meilibo".rand(10000,20000),

			'version' => '2.0',

			'body'=> "测试商品购买",
			'total_fee'=> 100,
			'mch_create_ip' => $_SERVER['REMOTE_ADDR'],
			'notify_url'=>static::$WechatPayConfig['NOTIFY_URL'],
			'limit_credit_pay'=>"1",
			'nonce_str'=>$this->getRandomStr(),
		);
		$str = $this->arrayToKeyValueString($param);
		$param['sign'] = $this->getSign($str);
		return $this->arrToXML($param);
	}

	protected function getRandomStr()
	{
		return md5('meilibo' . microtime() . 'weixin' . rand(100,9999));
	}

	/**
	 * 以post方式提交xml到对应的接口url
	 *
	 * @param string $xml  需要post的xml数据
	 * @param string $url  url
	 * @param bool $useCert 是否需要证书，默认不需要
	 * @param int $second   url执行超时时间，默认30s
	 * @throws WxPayException
	 *								----------------------------
	 */
	private static function postXmlCurl($xml, $useCert = false, $second = 30)
	{

		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);

		//如果有配置代理这里就设置代理
		curl_setopt($ch,CURLOPT_URL, static::$WechatPayConfig['PLACE_ORDER']);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);

		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
		$data = curl_exec($ch);
		//返回结果
		if($data){
			curl_close($ch);
			return $data;
		} else {
			$error = curl_errno($ch);
			curl_close($ch);
			// throw new Exception("curl出错，错误码:$error");
			return "<xml><return_code>FAIL</return_code><return_msg>".L('_NOT_SUPPERT_')."</return_msg></xml>";
		}
	}

	/**
	 * XML转数组
	 * 数组格式 array('大写xml的tag'	=>	'xml的value');
	 * 数组所有键为大写！！！-----重要！
	 */
	protected function xmlToArr($xml)
	{
		$parser = xml_parser_create();
		xml_parse_into_struct($parser, $xml, $data, $index);
		$arr = array();
		foreach ($data as $key => $value) {
			$arr[$value['tag']] = $value['value'];
		}
		return $arr;
	}

	/**
	 * 调用支付接口
	 *
	 * @param array $pre
	 */
	protected function initPrepayData($prepayData)
	{
		$appData = array(
			'appid' => $prepayData['APPID'],
			'partnerid' => $prepayData['MCH_ID'],
			'prepayid' => $prepayData['PREPAY_ID'],
			'package' => 'Sign=WXPay',
			'noncestr' => $this->getRandomStr(),
			'timestamp' => time()."",
		);

		ksort($appData);
		$str = $this->arrayToKeyValueString($appData);
		$appData['sign'] = $this->getSign($str);
		return $appData;
	}

	protected function arrayToKeyValueString($param)
	{

		ksort($param);

		$str = '';
		foreach($param as $key => $value) {
			$str = $str . $key .'=' . $value . '&';
		}
		return $str;
	}

	/**
	 * 获取签名
	 */
	public function getSign($str)
	{
		$str = $this->joinApiKey($str);
		return strtoupper(md5($str));
	}

	/**
	 * 拼接API密钥
	 */
	protected function joinApiKey($str)
	{
		return $str . "key=".static::$WechatPayConfig['APIKEY'];
		// return $str . "key=D4FF6168E5DC4452A46364ACF842301B";
	}

	/**
	 * 数组转XML
	 */
	protected function arrToXML($param, $cdata = false)
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

		return $xml;
	}
}