<?php
namespace Common\Util;

class ValiData
{

    /**
     * 当前对象
     * @var null
     */
    private static $_instance = null;


    /**
     * 错误信息
     * @var string
     */
    const DEFAULT_ERROR_CODE = 442; // 默认错误码


    /**
     * 校验数据信息
     * @var null
     */
    private $_value = null; // 要校验的数据
    private $_key = null; // 要校验的Key
    private $_required = true; // 必填 true-必填，false-非必填


    /**
     * @param bool $required 必填
     * @return int|ValiData
     */
    public static function getInstance($value, $key = null, $required = true){
        self::$_instance = new self($value, $key, $required);
        return self::$_instance;
    }

    public function __construct($value, $key = null, $required = true){
        $this -> _value = $value;
        $this -> _key = $key;
        $this -> _required = $required;
    }

    /**
     * 设置错误信息
     */
    private function _throwError( $errorMessage, $errorCode=null ){
        is_null($errorCode) && $errorCode = self::DEFAULT_ERROR_CODE;
        throw new \Exception($errorMessage, $errorCode);
    }

    /**
     * 必填
     */
    public function required(){

    }

    //============================================================================================
    // 以下是校验规则
    //============================================================================================
    /**
     * 手机号码校验
     * @param $val
     * @param $errorMessage
     */
    public function phone($code=null, $errorMessage=""){
        $pattern = "/^1(3\d|4[579]|5[012356789]|66|7[135678]|8\d|9[89])\d{8}$/";
        if( !preg_match($pattern, $this ->_value ) ){
            empty($errorMessage) && $errorMessage = "不符合手机号码规则";
            $this -> _throwError($errorMessage, $code);
        }
        return $this;
    }

    /**
     * 邮箱校验
     * @param $val
     * @param null $code
     * @param string $errorMessage
     */
    public function email($code=null, $errorMessage=""){
        if( !filter_var($this ->_value, FILTER_VALIDATE_EMAIL)){
            empty($errorMessage) && $errorMessage = "不符合邮箱规则";
            $this -> _throwError($errorMessage, $code);
        }

        return $this;
    }


    /**
     * 字符长度
     */
    public function length($min = null, $max = null, $code=null, $errorMessage=""){
        $length = strlen($this -> _value);

        // 范围
        if( $min > 0 && $max > 0){
            if(  $length < $min ||  $length > $max ){
                empty($errorMessage) && $errorMessage = "长度不能小于{$min}大于{$max}字符";
                $this -> _throwError($errorMessage, $code);
            }
        }elseif( $min > 0 && $length < $min  ){
            empty($errorMessage) && $errorMessage = "长度不能小于{$min}字符";
            $this -> _throwError($errorMessage, $code);
        }elseif(  $max > 0 && $length > $max  ){
            empty($errorMessage) && $errorMessage = "长度不能大于{$max}字符";
            $this -> _throwError($errorMessage, $code);
        }

        return $this;
    }

    /**
     *
     * @param null $code
     * @param string $errorMessage
     */
    public function money($code=null, $errorMessage=""){

    }

    public function qq($code=null, $errorMessage=""){
        $regx = "/^[1-9]\d{4,10}$/";
        if(!preg_match($regx, $this ->_value)){
            empty($errorMessage) && $errorMessage = "QQ号不正确";
            $this -> _throwError($errorMessage, $code);
        }

        return $this;
    }

    /**
     * 校验微信号
     * 长度是6-20，有字母数字下横杠
     */
    public function weixin($code=null, $errorMessage=""){
        $regx = "/^[a-zA-Z][a-zA-Z0-9-_]{5,19}$/";
        if(!preg_match($regx, $this ->_value)){
            empty($errorMessage) && $errorMessage = "微信号不正确";
            $this -> _throwError($errorMessage, $code);
        }

        return $this;
    }

    /**
     * 身份证号校验
     * @param null $code
     * @param string $errorMessage
     * @return bool
     */
    public function identity($code=null, $errorMessage=""){
        $val = strtoupper( $this -> _value );
        $regx = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        $arr_split = array();
        if(!preg_match($regx, $val))        {
            empty($errorMessage) && $errorMessage = "身份证号不正确";
            $this -> _throwError($errorMessage, $code);
        }

        if(15==strlen($val)) //检查15位
        {
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";

            @preg_match($regx, $val, $arr_split);
            //检查生日日期是否正确
            $dtm_birth = "19".$arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
            if(!strtotime($dtm_birth)){
                empty($errorMessage) && $errorMessage = "身份证号不正确";
                $this -> _throwError($errorMessage, $code);
            }
        }else{
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
            @preg_match($regx, $val, $arr_split);
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3]. '/' .$arr_split[4];
            if(!strtotime($dtm_birth)){
                empty($errorMessage) && $errorMessage = "身份证号不正确";
                $this -> _throwError($errorMessage, $code);
            }

            //检验18位身份证的校验码是否正确。
            //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
            $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
            $arr_ch = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
            $sign = 0;
            for ( $i = 0; $i < 17; $i++ ){
                $b = (int) $val{$i};
                $w = $arr_int[$i];
                $sign += $b * $w;
            }
            $n = $sign % 11;
            $val_num = $arr_ch[$n];
            if ($val_num != substr($val,17, 1)){
                empty($errorMessage) && $errorMessage = "身份证号不正确";
                $this -> _throwError($errorMessage, $code);
            }
        }

        return $this;
    }

    /**
     * 工商注册号、营业执照编号
     * 编号有15位和18位的
     * 15是全数字
     * 18位是由字母和数字组成，不包含（I、O、Z、S、V）
     */
    public function licinse($code=null, $errorMessage=""){

        $val = strtoupper( $this -> _value );
        $length = strlen($val);
        if( $length == 15 ){
            if(!is_numeric($val)){
                empty($errorMessage) && $errorMessage = "营业执照编号不正确";
                $this -> _throwError($errorMessage, $code);
            }
        }elseif( $length == 18 ){

            $regx = "/^[0-9ABCDEFGHGKLMNPQRTUWXY]{18}$)/";
            if(!preg_match($regx, $val)){
                empty($errorMessage) && $errorMessage = "营业执照编号不正确";
                $this -> _throwError($errorMessage, $code);
            }
        }

        return $this;
    }

    /**
     * 汉字
     */
    public function chinese($code=null, $errorMessage=""){
        $regx = "/^[\u0391-\uFFE5]+$/";
        if(!preg_match($regx, $this ->_value)){
            empty($errorMessage) && $errorMessage = "输入不正确";
            $this -> _throwError($errorMessage, $code);
        }

        return $this;
    }

    /**
     * 字母和数字
     */
    public function letterAndNum($code=null, $errorMessage=""){
        $regx = "/^[A-Za-z0-9]+$/";
        if(!preg_match($regx, $this ->_value)){
            empty($errorMessage) && $errorMessage = "输入不正确";
            $this -> _throwError($errorMessage, $code);
        }

        return $this;
    }

    /**
     * 校验是整数
     * @param null $code
     * @param string $errorMessage
     * @return $this
     * @throws \Exception
     */
    public function int( $code=null, $errorMessage="")
    {
        if ( !is_numeric($this ->_value) || strval(intval($this ->_value)) !== strval($this ->_value)) {
            empty($errorMessage) && $errorMessage = "必须为整数";
            $this -> _throwError($errorMessage, $code);
        }
        return $this;
    }









    /**
     * 日期校验
     */
    /*
    public static function date(){

    }
    */



    /**
     * http地址校验
     */
    /*
    public static function http(){

    }
    */

    /**
     * https地址校验
     */
    /*
    public static function https(){

    }
    */

    /**
     * url 地址校验，协议包括（http、https、udp、tcp、ftp）
     */
    /*
    public static function url(){

    }*/

    /**
     * 两个
     */
    /*
    public static function equalTo(){

    }
    */




    /**
     * 获取错误信息
     */
    /*
    public static function getAllError(){

    }
    */
































/*
	public static function checkNULL($key, $value)
	{
		if (is_null($value)) {
			Log("param [$key] cannot be null", 'DEBUG');
			E("[$key] 不能为空", C('PARAM_ERROR'));
		}
	}

	public static function checkNumber($key, $value, $min = NULL, $max = NULL)
	{
		if (!is_numeric($value)) {
			Log("param [ $key ] is not a number [ $value ]", 'DEBUG');
			E("[ $key ] 必须是数字", C('PARAM_ERROR'));
		}
		if (NULL !== $min && $value < $min) {
			Log("param [ $key ] is smaller than $min [ $value ]", 'DEBUG');
			E("[ $key ] 必须大于 $min", C('PARAM_ERROR'));
		}
		if (NULL !== $max && $value > $max) {
			Log("param [ $key ] is bigger than $max [ $value ]", 'DEBUG');
			E("[ $key ] 必须小于 $max", C('PARAM_ERROR'));
		}
	}


*/

    /**
     * @param $key [key描述]
     * @param $value [值]
     * @param null $min 最小值
     * @param null $max 最大值
     * @param $floatMaxLength [小数点最大长度]
     */
    /*
    public static function checkFloat($key, $value,  $min = NULL, $max = NULL, $floatMaxLength= null)
    {
        $pattern = "/^\d+(\.\d+)?$/";
        if(!preg_match($pattern, $value)){
            Log("param [ $key ] is not an integer [ $value ]", 'DEBUG');
            E("[ $key ] 必须是整数或小数", C('PARAM_ERROR'));
        }

        // 小数点最大长度
        $pattern = "/^\d+(\.\d{1,$floatMaxLength})?$/";
        if($floatMaxLength && !preg_match($pattern, $value)){
            Log("param [ $key ] is not an integer [ $value ]", 'DEBUG');
            E("[ $key ] 小数不能超过{$floatMaxLength}位！", C('PARAM_ERROR'));
        }

        if (NULL !== $min && $value < $min) {
            Log("param [ $key ] is smaller than $min [ $value ]", 'DEBUG');
            E("[ $key ] 必须大于 $min", C('PARAM_ERROR'));
        }
        if (NULL !== $max && $value > $max) {
            Log("param [ $key ] is bigger than $max [ $value ]", 'DEBUG');
            E("[ $key ] 必须小于 $max", C('PARAM_ERROR'));
        }
    }
    */

    /*
	public static function checkString($key, $value, $min = NULL, $max = NULL)
	{
		if (!is_string($value)) {
			Log::record("param [ $key ] is not a string [ $value ]", 'DEBUG');
			E("[ $key ] 必须是字符串", C('PARAM_ERROR'));
		}
		if (NULL !== $min && strlen($value) < $min) {
			Log::record("param [ $key ] is shorter than $min [ $value ]", 'DEBUG');
			E("[ $key ] 必须长于 $min", C('PARAM_ERROR'));
		}
		if (NULL !== $max && strlen($value) > $max) {
			Log::record("param [ $key ] is longer than $max [ $value ]", 'DEBUG');
			E("[ $key ] 必须短于 $max", C('PARAM_ERROR'));
		}
	}


	public static function checkStringArray($array, $min = NULL, $max = NULL)
	{
		foreach ($array as $k => $v) {
			self::checkString($k, $v, $min, $max);
		}
	}

	public static function checkUrl($key, $value)
	{
		$pattern = '/^(http|https):\/\//i';
		$reg_pattern = array ('options' => array ('regexp' => $pattern ) );
		if (false === filter_var ( $value, FILTER_VALIDATE_REGEXP, $reg_pattern )) {
			Log::record("param [ $key ] is not a valid url [ $value ]", 'DEBUG');
			E("[ $key ] 必须是一个 url", C('PARAM_ERROR'));
		}
	}

	public static function checkArray($key, $value, $arrCheck)
	{
		if (!is_array($value)) {
			Log::record("param [ $key ] is not an array [" . print_r ( $value, true ) . " ]", 'DEBUG');
			E("[ $key ] 不是一个数组", C('PARAM_ERROR'));
		}
		foreach ($arrCheck as $v) {
			if (!in_array($v, $value)) {
				Log::record("param [ $key ] contains no key/value [ $v ]", 'DEBUG');
				E("[ $arrCheck ]中有元素不在数组 [ $value ] 中", C('PARAM_ERROR'));
			}
		}
	}

	public static function checkArrayKey($needCheck, $arrChecked)
	{
		if (!is_array($arrChecked)) {
			Log::record('param the param to be detected is not an array', 'DEBUG');
			E("[ $arrChecked ] 不是一个数组", C('PARAM_ERROR'));
		}

		if (is_array($needCheck)) {
			foreach($needCheck as $value) {
				if (!array_key_exists($value, $arrChecked)) {
					Log::record("param $value does not exist in the array", 'DEBUG');
					E("[ $needCheck ] 中的值必须在 [ $arrChecked ] 键中", C('PARAM_ERROR'));
				}
			}
		} else {
			if (!array_key_exists($needCheck, $arrChecked)) {
				Log::record("param $needCheck does not exist in the array", 'DEBUG');
				E("[ $needCheck ] 必须在 [ $arrChecked ] 键中", C('PARAM_ERROR'));
			}
		}
	}

	public static function checkArrayNull($arrChecked)
	{
		if (!is_array($arrChecked)) {
			Log::record('param the param to be detected is not an array', 'DEBUG');
			E("[ $arrChecked ] 必须是一个数组", C('PARAM_ERROR'));
		}
		if (0 >= count($arrChecked)) {
			E("[ $arrChecked ] 数组不能为空", C('PARAM_ERROR'));
		}
	}
        */
}
