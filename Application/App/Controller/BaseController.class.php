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
    private $no_token_action_lst = array("grade_timer",'login','thirdlogin','rank','sendsms','updatesex','logout','bindmobile','weixinnotify','alinotify');


    function _initialize(){
        //::_initialize();
        Log::record("入口参数_POST：". json_encode(json_encode($_POST)), "INFO" );
        Log::record("入口参数_GET：". json_encode(json_encode($_GET)), "INFO" );
        Log::record("入口参数_FILES：". json_encode(json_encode($_FILES)), "INFO" );
       $this->token = isset($_REQUEST['token']) ? $_REQUEST['token'] : null;
        $action = strtolower(ACTION_NAME);
        if (!in_array($action, $this->no_token_action_lst)) {
            $this->verifyToken($this->token);
        }
        //检验$xy_version
//        $this->xy_version = isset($_SERVER['HTTP_XY_VERSION']) ? $_SERVER['HTTP_XY_VERSION'] : null;
//        Log::record("入口参数_HEADER：".$_SERVER['HTTP_XY_VERSION'], "INFO" );
////        var_dump($this->xy_version);die();
//        if($this->xy_version){
//            $this->xy_version = substr($this->xy_version,0,5);
//            $xy_version = substr($this->xy_version,0,5);
//            $xy_version_info = explode('.',$xy_version);
//            $xy_version_info = $xy_version_info[0].$xy_version_info[1].$xy_version_info[2];
//            //数据对比操作
//            $siteconfig = M('siteconfig')->where(array("id"=>1))->find();
//            //处理请求数据
//            $apkversion = substr($siteconfig['apkversion'],0,5);
//            $apkversion_info = explode('.',$apkversion);
//            $apkversion_info = $apkversion_info[0].$apkversion_info[1].$apkversion_info[2];
//            //检验数据$siteconfig['apkaddress']
//            $siteconfig['apkversion'] = substr($siteconfig['apkversion'],0,5);
//            if($xy_version_info < $apkversion_info){
//                /*$apkaddress_data = [
//                    "apk_url"=>$siteconfig['apkaddress'],
//                ];
//                $apkaddress_data = json_encode($apkaddress_data);*/
//                echo json_encode(array('code'=>3000,'desc'=>"该用户不是最新版本",'apk_url'=>$siteconfig['apkaddress']));
//                die();
//            }
//        }else{
//            $xy_version = null;
//        }
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
        return true;
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