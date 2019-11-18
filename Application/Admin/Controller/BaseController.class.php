<?php
namespace Admin\Controller;
use Think\Controller;
use Think\Log;

class BaseController extends Controller {
	protected $returnCode= null;
	protected $returnMsg = '';
	protected $returnData = null;
    private $token_regex = '/^[a-zA-Z0-9]{32}$/u';
    private $no_token_action_lst = array('login','daycountes','comdata');


    function _initialize(){
        Log::record("入口参数_POST：". json_encode(json_encode($_POST)), "INFO" );
        Log::record("入口参数_GET：". json_encode(json_encode($_GET)), "INFO" );
        $this->token = isset($_REQUEST['token']) ? $_REQUEST['token'] : null;
        $action = strtolower(ACTION_NAME);
        if (!in_array($action, $this->no_token_action_lst)) {
            $this->verifyToken($this->token);
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
        }
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
     * @return bool
     */
    protected function verifyToken($token){
        if(empty($token)){
            echo json_encode(array('code'=>5000,'desc'=>"token不存在"));
            die();
        }
        if (!is_string($token) || !preg_match($this->token_regex, $token) ) {  //无效的token类型
            echo json_encode(array('code'=>5001,'desc'=>"无效的token类型"));
            die();
        }
        //获取所有token值,并且判断当前token值是否在数据库里！
        $result_token = M("admin")->field("admin_token")->select();
        $info = $this->deep_in_array($token,$result_token);
        //检测session中的token是否存在数据库里!
        $info_session = $this->deep_in_array($_SESSION['token'],$result_token);
        if (empty($info) || empty($info_session)){  //会话已过期 或者 token不在数据库里
            echo json_encode(array('code'=>5002,'desc'=>"登录操作超时，请重新登录"));
            die();
        }
        return true;
    }

    /**判断当前值是否在数组里
     * @param $value    比较值
     * @param $array    数组
     * @return bool
     */
    protected function deep_in_array($value, $array) {
        foreach($array as $item) {
            if(!is_array($item)) {
                if ($item == $value) {
                    return true;
                } else {
                    continue;
                }
            }

            if(in_array($value, $item)) {
                return true;
            } else if($this->deep_in_array($value, $item)) {
                return true;
            }
        }
        return false;
    }

}


?>