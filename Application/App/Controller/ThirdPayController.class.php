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
 * 第三方支付的逻辑(非官方支付宝，微信支付).
 * Class ThirdPayAction
 */
class ThirdPayController extends BaseController
{

    /**
     * 初始化配置信息.
     * ThirdPayAction constructor.
     */
    public function __construct()
    {
        parent::__construct();// 初始化父类的构造方法.

    }

    /**
     * 深蓝 微信wap支付.
     *
     * @param string $orderNo 系统订单号.
     * @param int    $rmb     金额.
     */
    public function weiXinWap($orderNo, $rmb)
    {
        $post = array();
        $post['versionId'] = "001";
        $post['businessType'] = "1800";
        $post['transChanlName'] = "0008";
        $post['merId'] = "123";//商户id.
        $post['orderId'] = $orderNo;
        $post['transDate'] = date("YmdHis");
        $post['transAmount'] = "0.01";
        $post['backNotifyUrl'] = "http://www.baidu.com";
        $post['backurl'] = "http://www.baidu.com";
        $post['orderDesc'] = urlencode(mb_convert_encoding("测试", "GBK", "UTF-8"));//文档为UTF-8时需进行编码转换
        $post['signData']  = $this->arrayToKeyAndValue($post);
        $post['ReqSource'] = "WAP";//（浏览器传WAP，苹果传IOS，安卓传Android）不参与签名
    }

    /**
     * ascii码从小到大排序,并且进行加密.
     * @param array $param
     * @param $merKey
     * @return array|string
     */
    private function arrayToKeyAndValue($param = array(),$merKey)
    {
        ksort($param);
        $str = array();
        foreach($param as $k=>$v){
            $str[] = "$k=$v";
        }
        $str = implode("&", $str) . "&key=" . $merKey;
        $str = md5($str);
        $str = strtoupper($str);
        return $str;
    }

    /**
     * 深蓝扫码支付回调.
     */
    public function scanCallBack()
    {
        //后台通知时签名验证
        $vars = array();
        $merKey = C("PAYMENT.SCAN_PAY")['MERKEY'];
        $post = $_POST;
        $signData = isset($post['signData']) ? $post['signData'] : 0;
        unset($post['signData']);
        $calcSignData = $this->arrayToKeyAndValue($post, $merKey);

        file_put_contents("/tmp/scanCallBack.log", date("Y-m-d H:i:s",time()).":".json_encode($post)."|".PHP_EOL,FILE_APPEND);

        if($calcSignData <> $signData){
            exit("后台通知时签名错误");
        }else{
            $orderNo      = $post['orderId']; // 系统订单号.
            $deaild       = $post['ksPayOrderId'];    // 第三方订单.
            $rmb          = $post['transAmount'];// 充值金额.
            $content      = "第三方支付宝支付";
            // 进行逻辑操作(更新订单，用户加值，推荐人分红，站长分红).
            $updateStatus = OrderAction::updateOrder($orderNo, $deaild, $content, $rmb);
            // 如果回调逻辑操作全部成功.
            if ($updateStatus) {
                echo "ok"; exit();
            } else {
                echo "fail"; exit();
            }
        }
    }
}
