<?php
namespace Api\Controller;

use Api\Service\ChargeiosService;
use Api\Service\MemberService;
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
    public function getreceiptdata($token=null,$receipt,$rmb=null,$signature=null,$user_id=null,$transaction_id){
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
//            ParamCheck::checkInt("user_id",$data_repose['user_id'],1);
            //检验数据
            $receipt = $data_repose['receipt'];
            $user_id = RedisCache::getInstance()->get($data_repose['token']);
            //校验数据
//            if($data_repose['signature'] !== md5(strtolower($data_repose['user_id']))){
//                E("验签失败",2000);
//            }
            //应变特殊情况,当前没有用户id标识,而且该订单使用过,那么直接返回访订单已使用E("收据已被使用",4002);
            $apply_order = "APPLEPAY".$data_repose['transaction_id'];
            $order_used = M('chargedetail')->where(array("dealid"=>$apply_order))->count();
            if($order_used){
                E("收据已被使用",4002);
            }
            if(empty($data_repose['user_id'])){
                file_put_contents("/tmp/nouser.log","Apply--".date("Y-m-d H:i:s",time()).":".json_encode($data_repose)."".PHP_EOL,FILE_APPEND);
                E("此用户异常数据",2003);
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
            //请求验证结果
            $response = $this->curlRequestes($endpoint,$postData);
            $data = json_decode($response,true);
            //判断当前是否为沙盒模式
            if($data['status'] == '21007'){
                $endpointdev = C('APPLEPAY_URL_DEV');
                $response = $this->curlRequestes($endpointdev,$postData);
                $data = json_decode($response,true);
            }
            file_put_contents("/tmp/applepay.log","Apply--".date("Y-m-d H:i:s",time()).":".json_encode($data)."".PHP_EOL,FILE_APPEND);
            //判断返回的数据是否是对象
            if (empty($data)) {
                E('无效的响应数据',4000);
            }
            //判断购买时候成功
            if (!isset($data['status']) || $data['status'] != 0) {
                E('无效收据',4000);
            }
            $result = $data;
            //转换成数组
//             $result = $this->object_array_data($data);
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
            if ($result['status'] == 21007) {
                $dataes['content'] = '苹果支付21007';
            }
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
            $userKey = "userinfo_";     //redis缓存更新
            $member = M('member')->where(array("id"=>$data_repose['user_id']))->setInc('totalcoin',$coin);
            //4.获取对应当前用户充值成功后的虚拟币(修改用户的等级操作,不包括用户兑换操作,所以要统计充值) 新改动用户等级以totalcoin计算
//            $chargecoin = D('member')->where(array("id"=>$data_repose['user_id']))->setInc('chargecoin',$coin);
//            $deng_chargecoin = M('member')->where(array("id"=>$data_repose['user_id']))->getField("chargecoin");
            $deng_totalcoin = floor(MemberService::getInstance()->getOneByIdField($data_repose['user_id'],"totalcoin"));         //当前充值的虚拟币
            $lv_dengji = lv_dengji($deng_totalcoin);     //获取等级
            $result_dengji = array('lv_dengji'=>$lv_dengji);        //修改的字段值
            D('member')->updateDate($data_repose['user_id'],$result_dengji);        //修改等级
            RedisCache::getInstance()->getRedis()->hMset($userKey.$data_repose['user_id'], array('lv_dengji'=>$lv_dengji,'totalcoin'=>$deng_totalcoin));        //更改缓存等级与总虚拟币
            //5.获取用户的当前账户余额
            $updatecoins = D('member')->getFieldCoin($data_repose['user_id']);
            $updatecoin = floor($updatecoins['totalcoin'] - $updatecoins['freecoin']);
            $updatecoin = $updatecoin < 0 ?0:$updatecoin;
            $result_coin=[
                'coin'=> $updatecoin,
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

    /**CURL封装方法功能
     * @param $endpoint 请求地址
     * @param $postData 请求参数
     * @return mixed    返回类型数据
     */
    private function curlRequestes($endpoint,$postData)
    {
        //请求验证收据
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($ch);
        if($response){
            curl_close($ch);
            return $response;
        }else{
            $errno    = curl_errno($ch);    //错误code
            $errmsg   = curl_error($ch);    //错误message
            curl_close($ch);
            //判断错误,抛出异常
            if ($errno != 0) {
                E($errmsg,$errno);
            }
        }
    }

}