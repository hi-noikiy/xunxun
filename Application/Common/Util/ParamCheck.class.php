<?php
namespace Common\Util;

use Think\Log;

class ParamCheck
{
    const ERROR_CODE = 5004;
	public static function checkNULL($key, $value)
	{
		if (is_null($value)) {
			Log("param [$key] cannot be null", 'DEBUG');
			E("[$key] 不能为空", self::ERROR_CODE);
		}
	}

	public static function checkNumber($key, $value, $min = NULL, $max = NULL)
	{
		if (!is_numeric($value)) {
			Log("param [ $key ] is not a number [ $value ]", 'DEBUG');
			E("[ $key ] 必须是数字", self::ERROR_CODE);
		}
		if (NULL !== $min && $value < $min) {
			Log("param [ $key ] is smaller than $min [ $value ]", 'DEBUG');
			E("[ $key ] 必须大于 $min", self::ERROR_CODE);
		}
		if (NULL !== $max && $value > $max) {
			Log("param [ $key ] is bigger than $max [ $value ]", 'DEBUG');
			E("[ $key ] 必须小于 $max", self::ERROR_CODE);
		}
	}

	public static function checkInt($key, $value, $min = NULL, $max = NULL)
	{
		if (!is_numeric($value)) {
			Log("param [ $key ] is not a number [ $value ]", 'DEBUG');
			E("[ $key ] 必须是数字", self::ERROR_CODE);
		}
		if (strval(intval($value)) !== strval($value)) {
			Log("param [ $key ] is not an integer [ $value ]", 'DEBUG');
			E("[ $key ] 必须是整数", self::ERROR_CODE);
		}
		if (NULL !== $min && $value < $min) {
			Log("param [ $key ] is smaller than $min [ $value ]", 'DEBUG');
			E("[ $key ] 必须大于 $min", self::ERROR_CODE);
		}
		if (NULL !== $max && $value > $max) {
			Log("param [ $key ] is bigger than $max [ $value ]", 'DEBUG');
			E("[ $key ] 必须小于 $max", self::ERROR_CODE);
		}
	}

    /**
     * @param $key [key描述]
     * @param $value [值]
     * @param null $min 最小值
     * @param null $max 最大值
     * @param $floatMaxLength [小数点最大长度]
     */
    public static function checkFloat($key, $value,  $min = NULL, $max = NULL, $floatMaxLength= null)
    {
        $pattern = "/^\d+(\.\d+)?$/";
        if(!preg_match($pattern, $value)){
            Log("param [ $key ] is not an integer [ $value ]", 'DEBUG');
            E("[ $key ] 必须是整数或小数", self::ERROR_CODE);
        }

        // 小数点最大长度
        $pattern = "/^\d+(\.\d{1,$floatMaxLength})?$/";
        if($floatMaxLength && !preg_match($pattern, $value)){
            Log("param [ $key ] is not an integer [ $value ]", 'DEBUG');
            E("[ $key ] 小数不能超过{$floatMaxLength}位！", self::ERROR_CODE);
        }

        if (NULL !== $min && $value < $min) {
            Log("param [ $key ] is smaller than $min [ $value ]", 'DEBUG');
            E("[ $key ] 必须大于 $min", self::ERROR_CODE);
        }
        if (NULL !== $max && $value > $max) {
            Log("param [ $key ] is bigger than $max [ $value ]", 'DEBUG');
            E("[ $key ] 必须小于 $max", self::ERROR_CODE);
        }
    }

	public static function checkString($key, $value, $min = NULL, $max = NULL)
	{
		if (!is_string($value)) {
			Log::record("param [ $key ] is not a string [ $value ]", 'DEBUG');
			E("[ $key ] 必须是字符串", self::ERROR_CODE);
		}
		if (NULL !== $min && strlen($value) < $min) {
			Log::record("param [ $key ] is shorter than $min [ $value ]", 'DEBUG');
			E("[ $key ] 必须长于 $min", self::ERROR_CODE);
		}
		if (NULL !== $max && strlen($value) > $max) {
			Log::record("param [ $key ] is longer than $max [ $value ]", 'DEBUG');
			E("[ $key ] 必须短于 $max", self::ERROR_CODE);
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
			E("[ $key ] 必须是一个 url", self::ERROR_CODE);
		}
	}

	public static function checkArray($key, $value, $arrCheck)
	{
		if (!is_array($value)) {
			Log::record("param [ $key ] is not an array [" . print_r ( $value, true ) . " ]", 'DEBUG');
			E("[ $key ] 不是一个数组", self::ERROR_CODE);
		}
		foreach ($arrCheck as $v) {
			if (!in_array($v, $value)) {
				Log::record("param [ $key ] contains no key/value [ $v ]", 'DEBUG');
				E("[ $arrCheck ]中有元素不在数组 [ $value ] 中", self::ERROR_CODE);
			}
		}
	}

	public static function checkArrayKey($needCheck, $arrChecked)
	{
		if (!is_array($arrChecked)) {
			Log::record('param the param to be detected is not an array', 'DEBUG');
			E("[ $arrChecked ] 不是一个数组", self::ERROR_CODE);
		}

		if (is_array($needCheck)) {
			foreach($needCheck as $value) {
				if (!array_key_exists($value, $arrChecked)) {
					Log::record("param $value does not exist in the array", 'DEBUG');
					E("[ $needCheck ] 中的值必须在 [ $arrChecked ] 键中", self::ERROR_CODE);
				}
			}
		} else {
			if (!array_key_exists($needCheck, $arrChecked)) {
				Log::record("param $needCheck does not exist in the array", 'DEBUG');
				E("[ $needCheck ] 必须在 [ $arrChecked ] 键中", self::ERROR_CODE);
			}
		}
	}

	public static function checkArrayNull($arrChecked)
	{
		if (!is_array($arrChecked)) {
			Log::record('param the param to be detected is not an array', 'DEBUG');
			E("[ $arrChecked ] 必须是一个数组", self::ERROR_CODE);
		}
		if (0 >= count($arrChecked)) {
			E("[ $arrChecked ] 数组不能为空", self::ERROR_CODE);
		}
	}

    public static function  checkMobile($key, $value){
        // 判断手机号格式
//        $pattern = "/^1(3\d|4[579]|5[012356789]|6[012356789]|7[135678]|8\d|9[89])\d{8}$/";
        $pattern = " /^1\d{10}$/";
        if( !preg_match($pattern, $value) ){
            $errorMessage = $key."不正确，请确认后输入";
            E( "[" .$key ."]" .$errorMessage, self::ERROR_CODE);
        }

        return true;
    }

    public static function checkPwd($key,$value){
        //密码校验格式
        $pattern = "/^[A-Za-z0-9]+$/";
        if(!preg_match($pattern,$value)){
            $errorMessage = $key."密码存在字符和汉字，请确认后输入";
            E( "[" .$key ."]" .$errorMessage, self::ERROR_CODE);
        }

    }
}
