<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\RedisLock;
use Common\Util\Easemob;
use Think\Exception;
use Think\Log;
/**
 * 官方支付宝支付的逻辑.
 * Class AliPayAction
 */
class AliPayController extends BbaseController
{
    /**
     * 支付宝支付相关配置
     */
    protected static $configAli = array();

    /**
     * 初始化配置信息.
     * AliPayAction constructor.
     */
    public function __construct()
    {
        parent::__construct();// 初始化父类的魔术方法.

        $aliPay = C("ALIPAY");

       /* if (empty($aliPay)) {
            throw new Exception(L('_MISS_ALIPAY_CONFIG_'));
        }*/

        self::$configAli = $aliPay;

    }
    
       /*初始化我的钱包*/
    public function initmymoney($token){
        try{
            $userid = RedisCache::getInstance()->get($token);
            //根据用户id获取钻石总收入和钻石总消费
            $where=array('id'=>$userid);
            $cash=D('member')->getByqopenid($where,'diamond,free_diamond,totalcoin,freecoin,exchange_diamond');
            //剩余钻石数量
            $diamond=$cash[0]['diamond']-$cash[0]['free_diamond'] - $cash[0]['exchange_diamond'];
            $diamond = floor($diamond);
            if($diamond<0){
                $diamond = 0;
            }
            //剩余的虚拟币数量
            $coin=$cash[0]['totalcoin']-$cash[0]['freecoin'];
            $coin = floor($coin);
            if($coin<0){
                $coin = 0;
            }
            $result=[
                'diamond'=>$diamond,
                'coin'=>$coin,
            ];
            $this -> returnCode = 200;
            $this -> returnMsg = "操作成功";
            $this -> returnData=$result;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
        $this -> returnData();
        
    }
     /*初始化提现接口*/
    public function initcash($token){
        try{
            $userid = RedisCache::getInstance()->get($token);
            //通过userid 查询用户是否审核
            $checkestatus=D('approve')->getByfield(array('userid'=>$userid),'status');
       //     var_dump($checkestatus);die;
            //根据用户id获取钻石总收入和钻石总消费      
            $where=array('id'=>$userid);
            $cash=D('member')->getByqopenid($where,'diamond,free_diamond');
            //剩余钻石数量
            $diamond=$cash[0]['diamond']-$cash[0]['free_diamond'];
            $cashbili=D('siteconfig')->getOneByIdField('cash');
            //可提现金额数量 如果小于一百 提现按钮显示灰色  未绑定显示黑色
           // var_dump($diamond*$cashbili);die;
            $totalmoney=round($diamond*$cashbili);
           // var_dump($totalmoney);die;
            //获取该用户是否绑定
            $status=D('bindaplipay')->getbindmsg(array('userid'=>$userid));
          //  var_dump($status);die;
            if($totalmoney<100 || $status==null){
                $worko="0";//开关灰色不可提现
                if($sattus==null){
                    $bindstatus="0";//未绑定
                }else{
                    $bindstatus="1";//已经绑定
                }
            }else{
                $worko="1";//提现按钮显示
                $bindstatus="1";//已经绑定
            }
            // var_dump($totalmoney);die;
            if($checkestatus==null){
                $checkestatus=3;
            }
            $result=[
                'bindstatus'=>$bindstatus,
                'worko'=>$worko,
                'totalmoney'=>$totalmoney,
                'checkestatus'=>$checkestatus
            ];
            $this -> returnCode = 200;
            $this -> returnMsg = "操作成功";
            $this -> returnData=$result;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
        $this -> returnData();

    }
    
    /*初始化兑换接口*/
    public function inintexchange($token){
        try{
            $userid = RedisCache::getInstance()->get($token);
            //根据用户id获取钻石总收入和钻石总消费
            $where=array('id'=>$userid);
            $cash=D('member')->getByqopenid($where,'diamond,free_diamond,exchange_diamond');
            //剩余钻石数量
            $diamond=$cash[0]['diamond'] - $cash[0]['free_diamond'] - $cash[0]['exchange_diamond'];;
            $diamondexchangecoinbili=D('siteconfig')->getOneByIdField('exchangebili');
            //可以转换的M豆数量
            $coin=$diamond*$diamondexchangecoinbili;
            $diamond = floor($diamond);
            $coin = floor($coin);
            $result=[
                'diamond'=>$diamond,
                'coin'=>$coin,
                'scale'=>10,
            ];
            $this -> returnCode = 200;
            $this -> returnMsg = "操作成功";
            $this -> returnData=$result;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
        $this -> returnData();   
    }
    /*兑换M豆接口
     * $exchangediamond 要兑换的钻石数量
     * */
    public function diamondexchanggecoin($token,$exchangediamond){
        Log::record("兑换M豆传参：token----". $token.'-----钻石----'.$exchangediamond, "INFO" );
        try{
            $userid = RedisCache::getInstance()->get($token);

            //连接添加锁
            $redisService = [[C('REDIS_HOST'),C('REDIS_PORT'),0.1]];
            $redisLock = new RedisLock($redisService);
            $lockKey = 'redis_lock_'.$userid;
            $lockRes = $redisLock->lock($lockKey,3000);
            if (!$lockRes) {
                E("请求失败",5000);
            }

            
            //根据用户id获取钻石总收入和钻石总消费
            $where=array('id'=>$userid);
            $cash=D('member')->getByqopenid($where,'diamond,free_diamond,exchange_diamond,totalcoin,freecoin');
            //,bean_before,bean_after
            $diamondexchangecoinbili=D('siteconfig')->getOneByIdField('exchangebili');
            //剩余钻石数量
            $diamond=$cash[0]['diamond']-$cash[0]['free_diamond']-$cash[0]['exchange_diamond'];
            $diamond = floor($diamond);
            if($exchangediamond > $diamond){
                E("钻石不够",2004);
            }
            //可以转换的M豆数量
            $coin=$exchangediamond*$diamondexchangecoinbili;
            $coin = floor($coin);
            //根据比例换算出兑换的M豆数量
              $checkchsh = '/^[0-9][0-9]*$/';
            if(!preg_match($checkchsh,$exchangediamond)){
                E("提现金额必须为整数",2004);
            }
            // if($exchangediamond < 10){
            //     E("钻石必须大于10",2004);
            // }
            if($exchangediamond % 10 != 0){
                E("钻石必须10倍数",2004);
            }
            
            if($exchangediamond>$diamond){
                E('钻石不够',2000);
            }else{
               $totalcoin= D('member')->setInccoin($where,$coin,'totalcoin');
                $free_diamond=D('member')->setInccoin($where,$exchangediamond,'exchange_diamond');
                $cashs=D('member')->getByqopenid($where,'diamond,free_diamond,exchange_diamond');
                            //剩余钻石数量
                $diamonds=$cashs[0]['diamond']-$cashs[0]['free_diamond']-$cashs[0]['exchange_diamond'];
                $diamonds = floor($diamonds);
                // $coins=D('member')->getByqopenid($where,'totalcoin,freecoin');
                 $coinss=$diamonds*$diamondexchangecoinbili;
                 $coinss = floor($coinss);
                               if($totalcoin && $free_diamond){
                               $result=[
                                    'coin'=>$coinss,
                                    'diamond'=>$diamonds,
                               ];
                   //加入流水表(增加m豆)
                   $data=array(
                       'uid'=>$userid,
                       'action'=>"Modes",
                       'content'=>'增加M豆',
                       'addtime'=>date('Y-m-d H:i:s',time()),
                       'bean'=>$coin,
                       'bean_before'=>$diamond,
                       'bean_after'=>$diamond-$exchangediamond,
                       // 'client_version'=>$this->clientVersion,
                       // 'client_platform'=>$this->clientPlatform,
                   );
                  // var_dump($data);die;
                   $beandetail= D('beandetail')->addDatas($data);
                   //var_dump($beandetail);die;
                   //加入支出表(消耗钻石)
                   $datas=array(
                       'uid'=>$userid,
                       'action'=>"changes",
                       'content'=>'减少钻石',
                       'addtime'=>date('Y-m-d H:i:s',time()),
                       'coin'=>$exchangediamond,
                       'coin_before'=>$cash[0]['totalcoin'] - $cash[0]['freecoin'],
                       'coin_after'=>($cash[0]['totalcoin'] - $cash[0]['freecoin']) + $coin,
                       // 'client_version'=>$this->clientVersion,
                       // 'client_platform'=>$this->clientPlatform,
                   );
                   $coindetail=D('coindetail')->addData($datas);
                   //等级操作流程
                   $deng_totalcoin = floor(MemberService::getInstance()->getOneByIdField($userid,"totalcoin"));         //当前充值的虚拟币
                   $lv_dengji = lv_dengji($deng_totalcoin);     //获取等级
                   $result_dengji = array('lv_dengji'=>$lv_dengji);        //修改的字段值
                   D('member')->updateDate($userid,$result_dengji);        //修改等级

                   if($beandetail && $coindetail){
                    RedisCache::getInstance()->getRedis()->hset('userinfo_'.$userid, 'lv_dengji',$lv_dengji);
                    RedisCache::getInstance()->getRedis()->HINCRBY('userinfo_'.$userid, 'totalcoin',$coin);
                    RedisCache::getInstance()->getRedis()->HINCRBY('userinfo_'.$userid, 'exchange_diamond',$exchangediamond);
                    $lock = $redisLock->unlock($lockRes);
                   $this -> returnCode = 200;
                   $this -> returnMsg = "操作成功";
                   $this -> returnData=$result;
                   }
               }
            }
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
        $this -> returnData();
        // $this -> returnCode = 500;
        // $this -> returnMsg = "操作失败";
        // $this -> returnData=[];   
    }
    
       /**提现申请接口**/
    public function cash($token,$apliuserid,$cash,$totalcash,$type=null){
        try{
            $userid = RedisCache::getInstance()->get($token); 
            if($apliuserid==""){
                E('参数不正确',2002);
            }
            if($cash>$totalcash){
                E('你的可提现金额不足',2000);
            }
            if($cash>5000){
                E('最多提现金额不能大于5000',2001);
            }
            if($cash<100){
               E("提现金额不能小于100元",2003);
            }
            $checkchsh = '/^[0-9][0-9]*$/';
            if(!preg_match($checkchsh,$cash)){
                E("提现金额必须为整数",2004);
            }
           $user_info= D('member')->getByqopenid(array('id'=>$userid),'nickname,username');
           $cashbili=D('siteconfig')->getOneByIdField('cash');
           //可提现金额数量 如果小于一百 提现按钮显示灰色  未绑定显示黑色
           // var_dump($diamond*$cashbili);die;
           $diamond=round($cash/$cashbili);
            $data = array(
                "uid" => $userid,//主播ID
                "nickname" => $user_info[0]['nickname'],//主播昵称
                "cash" => $cash,//金额
                "diamond" => $diamond,//coin
                "apliuserid" => $apliuserid,//账户
                "cashtime" => date('Y-m-d H:i:s',time()),//提现时间
                "type" => "1",
                "username" => $user_info[0]['username'],                
            );
           // var_dump($data);die;
            $data=D('earncash')->addData($data);
            if($data){
                $this -> returnCode = 200;
                $this -> returnMsg = "操作成功";
            }

        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
        $this -> returnData();
        
    }
        /*绑定支付宝接口*/
    public function bindaplipay($token,$apliuserid,$type=null){
        try{
            $userid = RedisCache::getInstance()->get($token);
            $bindstatus=D('bindaplipay')->getbindmsg(array('userid'=>$userid));
            if($bindstatus){
                E('你已经绑定过支付宝',2000);
            }
            $user_info= D('member')->getByqopenid(array('id'=>$userid),'nickname,username');
            $data=array(
                "userid" => $userid,//主播ID
                "username" =>$user_info[0]['username'],//主播昵称
                "apliuserid" => $apliuserid,//账户
                "bindtime" => date('Y-m-d H:i:s',time()),//提现时间
                "nickname"=>$user_info[0]['nickname'],
            );
            $data=D('bindaplipay')->addData($data);
            if($data){
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
     * @param string  $orderNo 订单号.
     * @param integer $rmb     充值金额.
     */
    public function aliPay($orderNo, $rmb)
    {
      return  $this->initAlipayData($orderNo, $rmb);

    }

    /**
     * 支付宝支付的第三方回调.
     */
    public function aliNotify()
    {
        try{
        $time=date('Y-m-d H:i:s',time());
        $params = $_POST;
        file_put_contents("/tmp/PayNotify.log","AliPay--".date("Y-m-d H:i:s",time()).":".json_encode($params)."".PHP_EOL,FILE_APPEND);
        //进行基本参数验证
        if (empty($params) ||
            !isset($params['out_trade_no']) ||
            $params['seller_id'] != self::$configAli['PARTNER'] ||
            $params['trade_status'] != 'TRADE_SUCCESS'
        ) {
            echo "fail";
            die();
        }

        // 进行加密验签.
        $signVerifyStatus = $this->getSignVerify($params);
        // 验签成功.
            $orderNo      = $params['out_trade_no'];// 系统订单号.
            $deaild       = $params['trade_no'];    // 支付宝订单号(第三方订单).
            $rmb          = $params['total_amount'];// 充值金额.
            $content      = "官方支付宝支付";

            // 如果回调逻辑操作全部成功.
            $orderWhere["orderno"] = $orderNo;
            $updateStatus = OrderController::updateOrder($orderNo, $deaild, $content, $rmb);
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
                    'status'=>"支付宝",
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
                $this -> returnCode = 200;
                $this -> returnMsg = "操作成功";
                
            }else{
                Log::record("updateOrder失败----". $orderNo, "INFO" );
            }
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
        $this->returnData();
    }
    /*提现列表接口*/
    public function cashlist($token,$page){
        $token=$_REQUEST['token'];
        $page=$_REQUEST['page'];
        try{
            $where['status'] = array('neq','0');
            $field='id as cashid,cash,status,cashtime,type';         
            // 每页条数
            $size = 10;
            $pageNum = empty($size)?10:$size;
            $page = empty($page)?1:$page;
            $count =D('earncash')->earncount($where);
           //  var_dump($count);die;
            // 总页数.
            $totalPage = ceil($count/$pageNum);
            // 页数信息.
            $pageInfo = array("page" => $page, "pageNum"=>$pageNum, "totalPage" => $totalPage);
            $limit = ($page-1) * $size . "," . $size;
            $cash_list=D('earncash')->geterancashlist($where,$field,$limit,"cashtime desc");
            foreach($cash_list as $k=>$v){
                if($v['type']=="1"){
                    $cash_list[$k]['type']="支付宝";
                    $type="（微信）";
                }elseif($v['type']=="2"){
                    $cash_list[$k]['type']="微信";
                    $type="（微信）";
                }else{
                    $cash_list[$k]['type']="支付宝";
                    $type="（微信）";
                }
                if($v['status']=="1"){
                    $cash_list[$k]['status']="提现成功";
                }elseif($v['status']=="2"){
                    $cash_list[$k]['status']="联系客服";
                }
                    $cash_list[$k]['msg']="提现到".$type;
            }
            $result=[
                'cash_list'=>$cash_list,
                'pageinfo'=>$pageInfo,
            ];
            $this -> returnCode = 200;
            $this -> returnMsg = "操作成功";
            $this -> returnData=$result;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
        $this -> returnData();
    }
        /**
     * 初始化请求支付宝的数据，返回给APP
     *
     * @param $orderNo string  订单号.
     * @param $rmb     integer 充值金额.
     *
     * @return string
     */
    private function initAlipayData($orderNo, $rmb)
    {
      //  var_dump(static::$configAli['SUBJECT']);die;
        $subject = static::$configAli['SUBJECT'];
        $rmb = sprintf("%.2f", $rmb);
        $biz_content = "{\"body\":\"Isn't it good to live\",\"subject\":\"$subject\",\"out_trade_no\":\"$orderNo\",\"timeout_express\":\"90m\",\"total_amount\":\"$rmb\",\"product_code\":\"QUICK_MSECURITY_PAY\"}";
        //$biz_content = "{\"body\":\"Isn't it good to live\",\"subject\":\"$subject\",\"out_trade_no\":\"$orderNo\",\"timeout_express\":\"90m\",\"total_amount\":\"0.01\",\"product_code\":\"QUICK_MSECURITY_PAY\"}";
        $param = array(
            'alipay_sdk'  => 'alipay-sdk-php-20161101',
            'app_id'      => static::$configAli['APP_ID'],
            'biz_content' => $biz_content,
            'charset'     => 'UTF-8',
            'format'      => 'json',
            'method'      => 'alipay.trade.app.pay',
            'notify_url'  => static::$configAli['NOTIFY_URL'],
            'sign_type'   => 'RSA2',//签名类型
            'timestamp'   => '2018-05-08 08:30:11',//date('Y-m-d h:i:s', time()),
            'version'     => '1.0',
        ); 
      //  var_dump($param);die;
        ksort($params);
        $test = $this->getSignContent($param);
       // var_dump($test);die;
        $param['sign'] = $this->rsaSign($test, realpath(APP_PATH).'/Key/rsa_private_key.pem');
        //var_dump($param);die;
        $ret = http_build_query($param);
       // var_dump($ret);die;
        Log::record("alipay初始化----". json_encode($ret), "INFO" );
        return $ret;
    }
    
  public function aplisigns($token)
    {
        try{
            //$subject = static::$configAli['SUBJECT'];
            //$rmb = sprintf("%.2f", $rmb);
            // $biz_content = "{\"body\":\"Isn't it good to live\",\"subject\":\"$subject\",\"out_trade_no\":\"$orderNo\",\"timeout_express\":\"90m\",\"total_amount\":\"$rmb\",\"product_code\":\"QUICK_MSECURITY_PAY\"}";
            //$biz_content = "{\"body\":\"Isn't it good to live\",\"subject\":\"$subject\",\"out_trade_no\":\"$orderNo\",\"timeout_express\":\"90m\",\"total_amount\":\"0.01\",\"product_code\":\"QUICK_MSECURITY_PAY\"}";
            $param = array(
                'apiname'  => 'com.alipay.account.auth',
                'method'      => 'alipay.open.auth.sdk.code.get',
                'app_id' => static::$configAli['APP_ID'],
                'app_name'     => 'mc',
                'biz_type'      => 'openservice',
                'pid'      => '2088131391400150',
                'product_id'   => 'APP_FAST_LOGIN',
                'scope'   => 'kuaijie',
                'target_id'=>'Mua',
                'auth_type'     => 'LOGIN',
                'sign_type'     => 'RSA2',
            );
            ksort($params);
            $test = $this->getSignContent($param);
            $param['sign'] = $this->rsaSign($test, realpath(APP_PATH).'/Key/rsa_private_key.pem');
            $ret = http_build_query($param);
            $this -> returnCode = 200;
            $this -> returnMsg = "操作成功";
            $this -> returnData=$ret;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
        $this -> returnData();
        
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
    
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
    
                // 转换成目标字符集
                $v = $this->characet($v, $this->postCharset);
    
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
            $fileType = $this->fileCharset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //              $data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }
    
    
        return $data;
    }


    /**
     * 除去数组中的空值和签名参数
     *
     * @param array $para 签名参数组
     *
     * @return array
     */
    private function paraFilter($para) {
        $para_filter = array();
        while (list ($key, $val) = each ($para)) {
            if($key == "sign" || $val == "") { // sign_type
                continue;
            } else if ($key == 'biz_content') {
                $para_filter[$key] = json_encode($para[$key]);
            } else {
                $para_filter[$key] = $para[$key];
            }
        }
        return $para_filter;
    }

    /**
     * 对数组排序.
     * @param array $para 排序前的数组.
     *
     * @return array
     */
    private function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     *
     * @param array $para 需要拼接的数组
     *
     * @return string
     */
    private function createLinkstring($para) {
        $arg  = "";
        while (list ($key, $val) = each ($para)) {
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

        return $arg;
    }

    /**
     * RSA签名
     * @param string $data 待签名数据
     * @param mixed $private_key_path 商户私钥文件路径
     *
     * @return string
     */
    private function rsaSign($data, $private_key_path) {
        $pubKey = file_get_contents($private_key_path);
        $res = openssl_get_privatekey($pubKey);
        openssl_sign($data, $sign, $res,OPENSSL_ALGO_SHA256);
        openssl_free_key($res);
        return base64_encode($sign);
    }

    /**
     * 验证签名
     * @param array $params
     * @return bool
     */
    private function getSignVerify($params = array())
    {
        $sign = $params['sign'];
        unset($params['sign_type']);
        unset($params['sign']);
        $params = $this->argSort($params);
        $str = '';
        foreach($params as $key => $value) {
            $str = $str . "{$key}=" . $value . "&";
        }
        $str = substr($str, 0, -1);
        return $this->rsaVerify($str, $sign);
    }

    /**
     * 支付宝签名验证.
     * @param string $str 待验证字符串
     * @param string $sign 签名结果
     * @return bool
     */
    private function rsaVerify($str = '', $sign = '')
    {
        $public = openssl_pkey_get_public(file_get_contents(realpath(APP_PATH).'/Key/rsa_public_key.pem'));
        $verify = openssl_verify($str, base64_decode($sign), $public);
        openssl_free_key($public);

        return $verify  == 1 ? true : false;
    }
}
