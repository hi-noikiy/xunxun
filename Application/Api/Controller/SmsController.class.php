<?php

namespace Api\Controller;

require __ROOT__ . 'vendor/autoload.php';

use function AlibabaCloud\Client\json;
use Api\Service\MemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\Easemob;
use Think\Exception;
use Think\Log;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class SmsController extends BaseController
{

    /**
     * @var integer 验证码长度.
     */
    protected $captcha_length = 6;

    /**
     * 阿里短信配置项
     */
    private $ali_sms_accessKeyId = 'LTAI4FeEooRXD7CEAUUQqk2k';
    private $ali_sms_accessSecret = 'VbmMsoLpzY5nwgacu1QIMQjJ4dPq2J';
    private $ali_sms_product = 'Dysmsapi';
    private $ali_sms_action = 'SendSms';
    private $ali_sms_host = 'dysmsapi.aliyuncs.com';
    private $ali_sms_regionId = 'cn-hangzhou';
    private $ali_sms_signName = 'Mua';
    private $ali_sms_templateCode = 'SMS_173479310';

    private $user;

    public function __construct()
    {
        //$this->user = M('Member');
        //require_once APP_PATH.'../config.inc.php';
        // require_once APP_PATH.'../uc_client/client.php';
        parent::__construct();
    }

    public function sendYmSMS($phone)
    {
        require_once APP_PATH . 'Extension/SMS/ymsms.php';
        if (empty($phone)) {
            $this->responseError(L('_PHONENUM_NOT_R_'));
        }
        $phone = substr($phone, -11);
        $code = $this->generateRandomStr($this->captcha_length);

        $url = "http://www.api.zthysms.com/sendSms.do";//提交地址
        $username = 'fengjiehy';//用户名
        $password = '5SMK9K';//原密码
        $sendAPI = new sendAPI($url, $username, $password);
        $data = array(
            'content' => '验证码五分钟内有效' . $code . '【yomi直播】',//短信内容
            'mobile' => $phone,//手机号码
            'xh' => ''//小号
        );
        $sendAPI->data = $data;//初始化数据包
        $return = $sendAPI->sendSMS('POST');//GET or POST
        if (mb_substr($return, 0, 1) == "1") {
            $this->mmc->set('verify_code_' . $phone, $code, 5 * 60);
            $this->responseSuccess('短信发送成功');
        } else {
            $this->responseError('短信发送失败,稍后重试');
        }
    }
    /**
     * @param integer $phone
     */
    /*
    public function sendSMS($phone = null)
    {
        $url = 'http://106.3.37.50:9999/sms.aspx';
        $params = array(
            'userid' => '2426', // 企业ID
            'account' => 'wgwl', // 发送用户帐号
            'password' => 'abc123', // 发送帐号密码
            'mobile' => '', // 全部被叫号码,多个号码以半角逗号分开.
            'content' => '您的验证码为:%s, %d分钟内有效，切勿告知任何人。【灿星直播直播服务】', // 发送内容.
            'sendTime' => '', // 定时发送时间,为空表示立即发送，定时发送格式2010-10-24 09:08:10
            'action' => 'send', // 固定为发送.
            'extno' => '', // 扩展子号.
        );
        if (!is_string($phone) || !ctype_digit($phone) || strlen($phone) != 11) {
            $this->responseError('手机号不正确.');
        }
        if ($this->isLimited()) {
            $this->responseError('操作太过于频繁');
        }
        $code = $this->generateRandomStr($this->captcha_length);
        $expired_time = 5; // 单位分钟.
        $params['mobile'] = $phone;
        $params['content'] = sprintf($params['content'], $code, $expired_time);
        $this->mmc->set('verify_code_'.$phone, $code, $expired_time * 60);
        $xpath = null;
        try {
            $resp = CurlRequests::Instance()
                ->setHeader('User-Agent', '')
                ->setRequestMethod('POST')
                ->request($url, $params);
            $xpath = $this->getXpathObjectFromXmlStr($resp);
        } catch (Exception $e) {
            // curl 错误
        }
        if (empty($xpath)) {
            $this->responseError('发送验证码失败,请稍后重试', 500);
        }
        $status_code = $xpath->query('//returnsms/returnstatus/text()')->item(0);
        $msg = $xpath->query('//returnsms/message/text()')->item(0);
        if ($status_code != null && $msg != null) {
            $status_code = $status_code->nodeValue;
            $msg = $msg->nodeValue;
        }
        if ($msg == 'ok' && $status_code == 'Success') {
            $this->responseSuccess('验证码已经发送成功');
        } else {
            $this->responseError('操作失败', 2);
        }
    }
    */

    /**
     * 短信ApI  鸿联
     */
    /*
     public function sendSMS($phone = null){
          if (!is_string($phone) || !ctype_digit($phone) || strlen($phone) != 11) {
            $this->responseError('手机号不正确.');
        }
        if ($this->isLimited()) {
            $this->responseError('操作太过于频繁');
        }
        $ua = new UserAction();
        //验证账号状态是否可以登录
        $ua->accountStatus("sms",$phone);
        $code = $this->generateRandomStr($this->captcha_length);
        $expired_time = 5; // 单位分钟.
        $msg = array(
            "username" => "cytv",
            "password" => "cyTV395121",
            "epid" => "121593",
            "phone"=> $phone,
            "message"=>iconv("UTF-8", "GB2312//IGNORE",  "您的验证码为: ".$code."，5分钟内有效，切勿告知任何人。"),
            "linkid" => "",
            "subcode" => "",
            );
        $this->mmc->set('verify_code_'.$phone, $code, $expired_time * 60);

         $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://q.hl95.com:8061/?".http_build_query($msg));

            curl_setopt($ch, CURLOPT_HTTP_VERSION  , CURL_HTTP_VERSION_1_0 );
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_HTTPAUTH , CURLAUTH_BASIC);
            //curl_setopt($ch, CURLOPT_USERPWD  , $api_key);
            curl_setopt($ch, CURLOPT_POST, 0);
           // curl_setopt($ch, CURLOPT_POSTFIELDS,$msg);
            $send_text = curl_exec( $ch );
            curl_close( $ch );
     if (empty($send_text)) {
            $this->responseError('发送验证码失败,请稍后重试', 500);
        }
        $arrs = json_decode($send_text, true);
        if ($arrs == 0) {
            $this->responseSuccess('验证码已经发送成功');
        } else {
            $this->responseError('操作失败', 2);
        }

   }
   */

    /**
     * 短信API  螺丝帽
     * type  1找回密码 2绑定手机号
     */
    public function sendSMS($phone, $type = null)
    {
        $api_key = "api:key-" . C("SEND_MSG_SECRET_KEY");
        $phone = $_REQUEST['phone'];
        //$phone=I('post.phone');
        // var_dump($phone);die;
        try {

            //验证数据
            if (empty($phone)) {
                E("手机号不能为空", 2000);
            }
            ParamCheck::checkMobile("手机号", $phone);
            $phone = substr($phone, -11);
            // $sendNum = $this->mmc->get("SEND_MSG_" . $_SERVER['REMOTE_ADDR']);
            //     $sendNum= RedisCache::getInstance()->get("SEND_MSG_" . $_SERVER['REMOTE_ADDR']);
            $code = $this->generateRandomStr($this->captcha_length);
            $expired_time = 10; // 单位分钟.
            RedisCache::getInstance()->getRedis()->setex('verify_code_' . $phone, $expired_time * 60, $code);
            // var_dump($code);die;
            // if (in_array($phone, array('13800000000', '18888888888', '13888888888')) || substr($phone, 0,3) == '199') {
            if (in_array($phone, array('13800000000', '18888888888', '13888888888'))) {
                $code = 888888;
                RedisCache::getInstance()->set('verify_code_' . $phone, $code);
            } else {
                \Think\Log::record('阿里短信发送开始日志记录:时间:' . time() . ':手机号:' . $phone . ':验证码:' . $code);
                //切换到阿里短信发送
                $result = $this->aliSmsSend($phone, json_encode(array('code' => $code)));
                \Think\Log::record('阿里短信发送开始日志记录:时间:' . time() . ':手机号:' . $phone . ':验证码:' . $code . ':返回数据:' . json_encode($result));
                if (empty($result) || $result['Code'] != 'OK') {
                    E("发送验证码失败,请稍后重试", 2000);
                }

                //var_dump(C('LSM_SIGN'));die;
//                $msg = array("mobile"=>$phone, "message"=> "您的验证码为".$code.C('LSM_SIGN'));
//                // var_dump($msg);die;
//                $ch = curl_init();
//                curl_setopt($ch, CURLOPT_URL, "http://sms-api.luosimao.com/v1/send.json");
//
//                curl_setopt($ch, CURLOPT_HTTP_VERSION  , CURL_HTTP_VERSION_1_0 );
//                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
//                curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
//                curl_setopt($ch, CURLOPT_HEADER, FALSE);
//
//                curl_setopt($ch, CURLOPT_HTTPAUTH , CURLAUTH_BASIC);
//                curl_setopt($ch, CURLOPT_USERPWD  , $api_key);
//
//                curl_setopt($ch, CURLOPT_POST, TRUE);
//                curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);
//
//                $res = curl_exec( $ch );
//                var_dump($res);die;
//                curl_close( $ch );
//                if (empty($res)) {
//                    //$this->responseError(L('_CODE_FAILED_'));
//                    E("发送验证码失败,请稍后重试",2000);
//                }
//
//                $arr = json_decode($res, true);
//                log::record('sms_log--------'.json_encode($arr),'INFO');
//                if ($arr['error'] == '0') {
//                    RedisCache::getInstance()->set('verify_code_'.$phone, $code, $expired_time * 60);
//
//                    //   $_SESSION['verify_code_'.$phone]=$code;
//                    //session('verify_code_'.$phone,$code);
//
//                    //$this -> returnCode = 200;
//                    //E("验证码已经发送成功",2000);
//                    //$this->responseSuccess(L('_CODE_SUCCESS_'));
//                } else {
//
//                    E("发送验证码失败",2001);
//                    // $this->responseError(L('_OPERATION_FAIL_'));
//                }
            }

            $this->returnCode = 200;
            $this->returnMsg = "发送成功";
        } catch (\Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        $this->returnData($this->returnMsg);
    }

    /**
     * 代理预警短信
     * @param null $phone
     * @param null $message
     */
    public function sendWarning($phone = null, $message = null)
    {
        $api_key = "api:key-" . C("SEND_MSG_SECRET_KEY");
        if (empty($phone)) {
            $this->responseError("手机号不存在");
        }
        $phone = substr($phone, -11);
        $message = isset($message) ? $message : "您代理的限额不足，请及时充值";
        $msg = array("mobile" => $phone, "message" => C("SEND_MSG_PRE") . "提示:" . $message . L('_SMS_MESSAGE_TOW_'));
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://sms-api.luosimao.com/v1/send.json");

            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);

            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $api_key);

            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);

            $res = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            // curl 错误
        }
        if (empty($res)) {
            $this->responseError("第三方服务请求失败");
        }
        $arr = json_decode($res, true);

        if ($arr['error'] == '0') {
            $this->responseSuccess("短信发送成功");
        } else {
            $this->responseError($arr["msg"]);
        }
    }

    /*
    public function sendSMS($phone = null) {
        if (!is_string($phone) || !ctype_digit($phone) || strlen($phone) != 11) {
            $this->responseError('手机号不正确.');
        }
        if ($this->isLimited()) {
            $this->responseError('操作太过于频繁');
        }

        $url = "https://api.netease.im/sms/sendtemplate.action";
        $appKey = '0fb1d5e1310fb73fee14d2264535a613';
        $appSecret = '28c54deeb8a7';
        $nonce = 'temp';
        $code = $this->generateRandomStr($this->captcha_length);
        $expired_time = 5; // 单位分钟.
        $this->mmc->set('verify_code_'.$phone, $code, $expired_time * 60);
        $data = array('templateid' => '10008', 'mobiles' => '["'.$phone.'"]', 'params' => '["寻寻直播直播用户", "'.$code.'"]');
        $curTime = time();
        $checkSum = sha1($appSecret . $nonce . $curTime);
        $data = http_build_query($data);
        $opts = array (
                'http' => array(
                        'method' => 'POST',
                        'header' => array(
                                'Content-Type:application/x-www-form-urlencoded;charset=utf-8',
                                "AppKey:$appKey",
                                "Nonce:$nonce",
                                "CurTime:$curTime",
                                "CheckSum:$checkSum"
                        ),
                        'content' =>  $data
                ),
        );
        try {
            $context = stream_context_create($opts);
            $html = file_get_contents($url, false, $context);
        } catch (Exception $e) {
            // curl 错误
        }
        $arr = json_decode($html, true);

        if ($arr['code'] == '200') {
            $this->responseSuccess('验证码已经发送成功');
        } else {
            $this->responseError('操作失败', 2);
        }
       // echo $html;
    }
    */

    /**
     * 短信接口 创蓝
     *
     */
    /*
    public function sendSMS($phone = null)
    {
        if (!is_string($phone) || !ctype_digit($phone) || strlen($phone) != 11) {
            $this->responseError('手机号不正确.');
        }
        if ($this->isLimited()) {
            $this->responseError('操作太过于频繁');
        }

        $url = 'http://222.73.117.158:80/msg/HttpBatchSendSM';

        $request = CurlRequests::Instance()->setRequestMethod('get');
        $code = $this->generateRandomStr($this->captcha_length);
        // 验证码过期时间
        $expired_time = 5;
        $this->mmc->set('verify_code_'.$phone, $code, $expired_time * 60);
        $param = array(
            'account'=>'haienwl888',
            'pswd'=>'Hewl123456',
            'mobile'=>$phone,
            'msg'=>"【寻寻直播直播服务】您的验证码为: ".$code."，5分钟内有效，切勿告知任何人。【寻寻直播】",
            'needstatus'=>true,
            );

        $response = $request->request($url, $param);

        $spices = explode(',', $response);
        if ($spices[1] != 0) {
            $this->responseError('短信发送失败');
        }

        $this->responseSuccess('发送成功');

    }
    */

    /**
     * 验证手机号是否正确.
     * @param int $phone
     * @param string $captcha
     */
    public function verify($phone = null, $captcha = null, $referee_id = '')
    {
        if (empty($phone)) {
            $this->responseError(L('_PHONENUM_NOT_R_'));
        }
        $phone = substr($phone, -11);

        if (!is_string($captcha) || strlen($captcha) != $this->captcha_length) {
            $this->responseError(L('_CODE_TYPE_FAILED_'));
        }
        $code = $this->mmc->get('verify_code_' . $phone);
        $ua = new UserAction();
        //验证账号状态是否可以登录
        $ua->accountStatus("sms", $phone);
        if (in_array($phone, array('15002873197', '18583858486', '18384580577', '13438200914', '18888888888', '18382408534', '13908065475'))) {
            $code = $captcha = 88888;
        }
        if ($code == null) {
            $this->responseError(L('_CODE_OVERDUE_'), 1);
        } else if ($code != $captcha) {
            $this->responseError(L('_CODE_FAIL_'), 2);
        }

        $user_info = Text::inst('api')->setClass('User')->getMobile("id,username", $phone);

        if (empty($user_info)) {
            $user_id = $this->registerWithPhone($phone, $referee_id, $_REQUEST['password']);
            if ($user_id < 0) {
                $this->responseError(L('_CODE_SYSTEM_ERROR_'), 3);
            }
            // 这里的username可能不一定是手机号了.所以尝试获取一次.理论上一定有.
            $user_info = Text::inst('api')->setClass('User')->getByUid("id,username", $user_id);
        }
        //联盟站长推广所得
        if (!empty($_COOKIE['masterid'])) {
            MasterAction::regForMaster($_COOKIE['masterid'], $user_info['id']);
        }
        // 如果用户之前自动登录过,还有token,删除原来的token.
        $token = TokenHelper::getInstance()->get($user_info['id']);
        if (!empty($token)) {
            TokenHelper::getInstance()->delete($token);
        }

        $resp = UserAction::parseLoginSuccessResp($user_info['username'], $user_info['id']);

        $this->responseSuccess($resp);
    }

    /**
     * 校验手机验证码是否成功
     * @param int $phone 手机号
     * @param int $captcha 验证码
     */
    public function checkCaptcha($phone = null, $captcha = null)
    {
        if (empty($phone)) {
            $this->responseError(L('_PHONENUM_NOT_R_'));
        }
        $phone = substr($phone, -11);

        if (!is_string($captcha) || strlen($captcha) != $this->captcha_length) {
            $this->responseError(L('_CODE_TYPE_FAILED_'));
        }
        $code = $this->mmc->get('verify_code_' . $phone);
        //如果验证码正确
        if ($code == $captcha) {
            $this->responseSuccess("验证码验证成功");
        } else {
            $this->responseError("验证码错误");
        }
    }

    /**
     * (之前需检查用户是否在)通过手机号进行注册.
     * @param $phone
     *
     * @return integer 注册之后的用户ID,小于等于0,表示失败.请参见ucenter的返回值.
     */
    private function registerWithPhone($phone, $referee_id, $password = '')
    {
        $password = !empty($password) ? $password : $password = $phone;//$this->responseError('密码不能为空') ;
        $username = $phone;
        $nickname = substr($phone, 0, 3) . "****" . substr($phone, 7, 4);
        do {
            $roomnum = rand(1000000000, 1999999999);
        } while ($this->checkIt($roomnum) == '');
        $new_user_info = array(
            'username' => $username,
            'nickname' => $nickname,
            'password' => md5($password),
            'password2' => $this->pswencode($password),
            'regtime' => time(),
            'email' => '',
            'curroomnum' => $roomnum,
            'mobile' => $phone,
        );

        if (is_numeric($referee_id) && ($referee_id > 0)) {


            $referee_id = intval($referee_id);
            $referee_info = M("Member")->field("id,o_id,a_id,b_id,c_id")->where("id = {$referee_id}")->find();
            if (!empty($referee_info)) {
                $new_user_info['referee_id'] = $referee_id;
                $new_user_info['o_id'] = $referee_info['o_id'];
                $new_user_info['a_id'] = $referee_info['a_id'];
                $new_user_info['b_id'] = $referee_info['b_id'];
                $new_user_info['c_id'] = $referee_info['c_id'];
            }
        }

        $user_id = Text::inst('api')->setClass('User')->create($new_user_info);

        $new_user_info['id'] = $user_id;

        $Jmessage = new JmessageAction();
        $Jmessage->jmRegist($new_user_info);

        $username = "user_" . $user_id;

        Text::inst('api')->setClass('User')->update(array("username" => $username), array("id" => $user_id));
        // 新注册房间状态写入缓存
        setRegistRoom($user_id, $roomnum);
        return $user_id;
    }

    /**
     * 生成随机字符串.
     *
     * @param int $length 需要生成的长度.
     * @param string $table 需要生成的字符串集合.
     *
     * @return string
     */
    protected function generateRandomStr($length = 6, $table = '0123456789')
    {
        $code = '';
        if ($length <= 0 || empty($table)) {
            return $code;
        }
        $max_size = strlen($table) - 1;
        while ($length-- > 0) {
            $code .= $table[rand(0, $max_size)];
        }
        return $code;
    }

    /**
     * 绑定手机号
     * @param $token token值
     * @param phone  手机号
     * @param verify 验证码
     * @param password 密码
     */
    public function addMobile($token, $phone, $verify, $password)
    {
        $channel = $_REQUEST['channel'];
        if ($channel != "") {
            $channels = trim($channel);
            if ($channels == 'xunxun') {
                $channels = 1;
            } elseif ($channels == "qudao_1") {
                $channels = 2;
            } elseif ($channels == "qudao_2") {
                $channels = 3;
            }
        }
        $userInfo = TokenHelper::getInstance()->get($token);
        if (empty($phone) || empty($verify) || empty($password)) {
            $this->responseError(L('_PARAM_ERROR_'));
        }
        $phone = substr($phone, -11);
        $code = $this->mmc->get('verify_code_' . $phone);

        if ($verify != $code) {
            $this->responseError('验证码错误');
        }
        $checkphone = "/^1[3|4|5|7|8]\d{9}$/";
        if (!preg_match($checkphone, $phone)) {
            $this->responseError('请输入正确的手机号');
        }

        if (strlen($password) < 6) {
            $this->responseError('密码不少于6位');
        }
        //对应邀请注册用户查询获取邀请码
        $register_inviterCode = M('share_member')->where(array("phone" => $phone))->select();
        if ($register_inviterCode) {
            $parent_id = M('member')->where(array("inviter_code" => $register_inviterCode[0]['inviter_code']))->getField('id');
        } else {
            $parent_id = 0;
        }

        if (empty($userInfo)) {
            $this->responseError('no such user');
        } else {

            $userinfo = Text::inst('api')->setClass('User')->getMobile("id", $phone);
            if (!empty($userinfo)) {
                $this->responseError('该手机号已被其他用户绑定');
            }
            $list_sounds = M('languagevideo_price')->where(array("type" => 1))->select();
            $list_videos = M('languagevideo_price')->where(array("type" => 2))->select();
            $data['username'] = $phone;
            $data['mobile'] = $phone;
            $data['password'] = md5($password);
            $data['parent_id'] = $parent_id;
            $data['is_video_output'] = 1;
            $data['is_sound_output'] = 1;
            $data['intro'] = "你主动我们就会有故事～";
            $data['channel'] = $channels;
            $data['sound_price'] = $list_sounds[0]['prices'];
            $data['video_price'] = $list_videos[0]['prices'];
//            $data['coinbalance'] = 300;
            $id = Text::inst('api')->setClass('User')->update($data, array("id" => $userInfo['uid']));
//            file_put_contents("addphoe.txt",$userInfo['uid']);
            if ($id) {
                //插入数据
                /* $dataes['user_id'] = $userInfo['uid'];
                 $dataes['content'] = "该用户注册奖励300寻币";
                 $dataes['first_bean'] = "300";
                 $dataes['addtime'] = time();
                 //加入数据
                 M('member_firstcoin')->add($dataes);*/
                //用户绑定手机 +10
                $lively = M('lively')->where(array("type" => 1, "id" => 10))->find();     //获取绑定手机分值
                $activenum = M('member')->where(array("id" => $userInfo['uid']))->getField('activenum');
                $userinfo['activenum'] = $activenum + $lively['pointes'];
                //更新用户数据表
                M('member')->where(array('id' => $userInfo['uid']))->save($userinfo);
                $this->responseSuccess('绑定成功');
            } else {
                $this->responseError('绑定失败');
            }
//            $id ? $this->responseSuccess('OK') : $this->responseError('绑定失败');
        }
    }

    /**
     * 从XML字符串中生成DOMXPath对象,用于后续执行Xpath操作.
     *
     * @param $source xml格式的字符串.
     *
     * @return DOMXPath
     *
     */
    protected function getXpathObjectFromXmlStr($source)
    {
        $dom = new DOMDocument();
        @$dom->loadXML($source);
        $xpath = new DOMXPath($dom);
        return $xpath;
    }

    /*
     *阿里短信发送
    */
    private function aliSmsSend($phone, $data)
    {
        AlibabaCloud::accessKeyClient($this->ali_sms_accessKeyId, $this->ali_sms_accessSecret)
            ->regionId('cn-hangzhou')
            ->asDefaultClient();
        $result = AlibabaCloud::rpc()
            ->product($this->ali_sms_product)
            // ->scheme('https') // https | http
            ->version('2017-05-25')
            ->action($this->ali_sms_action)
            ->method('POST')
            ->host($this->ali_sms_host)
            ->options([
                'query' => [
                    'RegionId' => $this->ali_sms_regionId,
                    'PhoneNumbers' => $phone,
                    'SignName' => $this->ali_sms_signName,
                    'TemplateCode' => $this->ali_sms_templateCode,
                    'TemplateParam' => $data,
                ],
            ])
            ->request();
        return $result->toArray();
    }
}
