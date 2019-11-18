<?php

namespace Api\Controller;

use Think\Controller;
use Common\Util\RedisCache;
use Think\Log;

class BaseController extends Controller {
	protected $returnCode= null;
	protected $returnMsg = '';
	protected $returnData = null;
    private $token_regex = '/^[a-zA-Z0-9]{32}$/u';

    private $no_token_action_lst = array('sendcomment','sendreplymsg','sendforummsg',"option","grade_timer",'login','thirdlogin','rank','sendsms','updatesex','logout','bindmobile','weixinnotify','alinotify','ranklist','getreceiptdata','addvisitor');
    private $real_name_action_list = array('addforum','addreply','create');

    protected $clientPlatform;
    protected $clientPlatformVersion;
    protected $clientVersion;
    protected $clientBuild;
    protected $clientChannel;

    function _initialize(){
        //::_initialize();
        Log::record("入口参数_POST：". json_encode(json_encode($_POST)), "INFO" );
        Log::record("入口参数_GET：". json_encode(json_encode($_GET)), "INFO" );
        Log::record("入口参数_FILES：". json_encode(json_encode($_FILES)), "INFO" );
        Log::record("请求头参数_HEADER：". json_encode($_SERVER), "INFO" );
       $this->token = isset($_REQUEST['token']) ? $_REQUEST['token'] : null;
        $action = strtolower(ACTION_NAME);
        if (!in_array($action, $this->no_token_action_lst)) {
            $this->verifyToken($this->token);
        }

        $uid = RedisCache::getInstance()->get($this->token);
        $this->realname($uid,$action);

        //取出客户端信息
        $array=explode(',', $_SERVER['HTTP_XY_PLATFORM']);
        $verArray = explode(',', $_SERVER['HTTP_XY_VERSION']);
        $this->clientChannel = $_SERVER['HTTP_XY_CHANNEL'];
        if (empty($this->clientChannel)) {
            $this->clientChannel = $_SERVER['HTTP_USER_AGENT'];
        }

        $this->clientPlatform = $array[0];
        $this->clientPlatformVersion = $array[1];
        $this->clientVersion = $verArray[0];
        $this->clientBuild = $verArray[1];

        $clientVersion = $this->clientVersion;

        //取出服务器配置信息
        $siteconfig = M('siteconfig')->where(array("id"=>1))->find();

        if ($this->clientPlatform == "iOS") {
            $iOSMinVersion = $siteconfig['ipaversion'];
            if (version_compare($clientVersion, $iOSMinVersion, '<')) {
                //1.0.5之前版本都是企业签名包，客户端渠道号有错误，统一判定为web版本，并且更新错误的使用了appStore的地址
                if ($this->clientVersion <= $this->stringToarray('1.0.7')) {
                    echo json_encode(array('code'=>3000,'desc'=>"该用户不是最新版本",'appStore'=>$siteconfig['webaddress'],'web'=>$siteconfig['webaddress']));
                    die();
                }
                else if ($this->clientChannel == "web") {
                    echo json_encode(array('code'=>3000,'desc'=>"该用户不是最新版本",'web'=>$siteconfig['webaddress']));
                    die();
                }
                else { // ($channel == "appStore") {    //不是web的都判定为app商店
                    if ($action != 'avatar') {
                        echo json_encode(array('code'=>3000,'desc'=>"该用户不是最新版本",'appStore'=>$siteconfig['iosaddress']));
                        die();
                    }

                }
            }
        }else {// if ($platform == "Android") {  //不是ｉＯＳ的都判定为android
            if ($action != 'avatar') {
                $androidMinVersion = $siteconfig['apkversion'];
                if (version_compare($clientVersion, $androidMinVersion, '<')) {
                    echo json_encode(array('code'=>3000,'desc'=>"该用户不是最新版本",'apk_url'=>$siteconfig['apkaddress']));
                    die();
                }
            }
        }
    }

    /**
     * 初始化返回数据结构以josn形式
     */
	protected function returnData($returnMsg=null){
        if(!$this -> returnCode &&  $this -> returnMsg) {
            $this->returnCode = 601;
            $this->returnMsg = "操作失败";
        }else{
            $this -> returnCode = $this -> returnCode ? $this -> returnCode : 200;
            $this -> returnCode == 200 && $this -> returnMsg = "操作成功";
//            $this -> returnCode == 200;
        }
       /* if($returnMsg){
            $this -> returnMsg = $returnMsg;
        }else{
            $this -> returnMsg = "操作成功";
        }*/

		$data = [
			"code" => $this -> returnCode,
			"desc" => $this -> returnMsg,
		];

        if( !is_null( $this -> returnData ) ){
            $data['data'] = $this -> returnData;
        }

		$result = json_encode($data , JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        Log::record("返回数据：". $result, 'INFO');
        echo $result;
		die;
	}

    /**字符串格式转换
     * @param $data_version
     * @return array|string
     */
    protected function stringToarray($data_version){
//        $xy_version = substr($data_version,0,5);
//        $xy_version_info = explode('.',$xy_version);
        $data_version = explode(',',$data_version);
        $xy_version_info = explode('.',$data_version[0]);
        $xy_version_info = $xy_version_info[0].$xy_version_info[1].$xy_version_info[2];
        return $xy_version_info;
    }

    /**
     * 校验
     * @param $token
     * @param null $username
     * @return bool
     */
    protected function verifyToken($token,$username=null){
        if(empty($token)){
//            E("token不存在",5000);
            echo json_encode(array('code'=>5000,'desc'=>"token不存在"));
            die();
        }
        if (!is_string($token) || !preg_match($this->token_regex, $token) ) {  //无效的token类型
//            E("无效的token类型",5001);
            echo json_encode(array('code'=>5001,'desc'=>"无效的token类型"));
            die();
        }
        $info = RedisCache::getInstance()->get($token);
        if (empty($info)) {  //会话已过期
//            E("登录操作超时，请重新登录",5002);
            echo json_encode(array('code'=>5002,'desc'=>"登录操作超时，请重新登录"));
            die();
        }
        /*if ($info['username'] !== $username && $username !== null) {  //token非法
            E("登录操作超时，请重新登录",5003);
        }*/
        $blackList = D("black_list")->getList(); 
        if (in_array($info, $blackList)) {
            echo json_encode(array('code'=>500,'desc'=>"您的账户封禁异常"));
            exit;
        }
        return true;
    }

    protected function realname($userid=0,$action='')
    {
        if (in_array($action, $this->real_name_action_list)) {
            $attestation = RedisCache::getInstance()->getRedis()->hget('userinfo_'.$userid,'attestation');
            if ($attestation != 1) {
                echo json_encode(array('code'=>900,'desc'=>"操作需要实名认证"));
                exit;
            }
        }
    }

    /**
     * 分页计算
     * @param $total
     * @param $currPage
     * @return array
     */
    protected function getPage($currPage, $limit = 10, $start = 0){
        $currPage = $currPage > 0 ? $currPage : 1;
        $start = ceil($currPage -1) * $limit + $start;

        return [
            'limit' => $limit,
            'page' => $currPage,
            'start' => $start,
        ];
    }
    
        /**
     * 发起CURL请求
     *
     * @param string url
     * @param bool isPost
     * @param array $data
     */
    protected function curlRequest($url, $isPost, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($isPost) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        } else {
            $url =  $url . '?' . http_build_query($data);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        
        $output = curl_exec($ch);
        curl_close($ch);
        
        return $output;
    }
}


?>