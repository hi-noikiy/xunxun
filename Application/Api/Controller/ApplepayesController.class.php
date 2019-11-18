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
 * 苹果支付相关接口.
 * Class ApplePayAction
 */
class ApplePayController extends BaseController {

	protected static $appleConfig = array(); // 苹果支付配置.

	public function __construct()
	{
		parent::__construct();

		$applePay = C('APPLE');

		/*if (empty($applePay)) {
			throw new Exception(L('_MISS_APPLE_CONFIG_'));
		}*/
		self::$appleConfig = $applePay;

		self::$appleConfig['ENV'] = 'product'; // 默认让它处于正式环境
	}

	/**
	 * @param string $certificate 支付凭证.
	 *
	 * @return boolean
	 */
	public function applePay($certificate)
	{
		// 请求appleStore验证支付证书.
		$responseFromAppleStore = $this->sendReceiptData($certificate);

		$res = json_decode($responseFromAppleStore, true);
		// 如果状态值为21007，则表示处于沙箱环境.
		if (isset($res["status"]) && ($res["status"] == '21007')) {
				// 改变环境为沙箱环境，再次请求appStore进行校验.
				self::$appleConfig["ENV"] = 'sandbox';
				$responseFromAppleStore = $this->sendReceiptData($certificate);
		}

		// 验证苹果验证证书的状态.
		$responseStatus = $this->checkResponse($responseFromAppleStore);
		return $responseStatus;
	}

	/**
	 * 获取验证地址，分为沙盒和正式环境
	 *
	 * @return string
	 */
	protected function getVerifyUrl()
	{
		return self::$appleConfig['ENV'] == 'sandbox' ? self::$appleConfig['VERIFY_URL_SANDBOX'] : self::$appleConfig['VERIFY_URL_PRODUCT'];
	}

	/**
	* 发送数据到AppStore校验凭证
	*
	* @param string $certificate 苹果支付凭证.
	* @return string
	*/
	protected function sendReceiptData($certificate)
	{
		$data = json_encode(array('receipt-data'=>$certificate));

	//	$request = CurlRequests::Instance()->setRequestMethod('post')->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
	//	$response = $request->request($this->getVerifyUrl(), $data);
			$response = $this->curlRequest($this->getVerifyUrl(),1, $data);

        return $response;
	}

	/**
	* 校验AppStore返回数据
	*
	* @param string $response json格式，appStore返回结果
	* @return bool
	*/
	protected function checkResponse($response = '')
	{
		$response =  json_decode($response, true);
		if (!is_array($response) || empty($response)) {
			return false;
		}

		return $response['status'] === 0 ? true : false;
	}

}