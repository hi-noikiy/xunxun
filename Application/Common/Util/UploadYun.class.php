<?php
namespace Common\Util;
use Think\Log;
/**
 * 阿里云实人认证
 * Class UploadYun
 */
//namespace app\Util;
//use think\Config;
date_default_timezone_set('Asia/Shanghai');
class UploadYun{
    private $publicParam;
    private $accessKeyId = "";
    private $accessKeySecret = "";

    private static $_instances = null;


    /**
     *  初始化
     * @return UploadYun|null
     */
    public static function getInstance()
    {
        if (!self::$_instances) {
            self::$_instances = new self();
        }

        return self::$_instances;
    }


    public function __construct(){
        $conf['accessKeyID'] = "LTAIB1xELc9MzLx5";
        $conf['accessKeySecret'] = "fMoCSlTQfoHDagZa45zfxgqfA6eNHS";
        $this->accessKeySecret = $conf['accessKeySecret'];
        $this->accessKeyId     = $conf['accessKeyID'];
        $Timestamp = date('Y-m-d\TH:i:s\Z', time() - date('Z'));
        $this->publicParam = array(
            'Format'    =>  'XML', //支持 JSON 与 XML，默认为 XML。
            'Version'    =>  '2018-09-16', //API版本号
            'AccessKeyId' => $conf['accessKeyID'],      //密钥ID
            'SignatureMethod'    =>  'HMAC-SHA1',
            'Timestamp'    => $Timestamp, //发送时间 格式0000-00-00 00:00:00
            'SignatureVersion'    =>  '1.0',//签名算法版本,目前版本是1.0
            'SignatureNonce'    =>  md5(microtime()), //唯一随机数，用于防止网络重放攻击。用户在不同请求间要使用不同的随机数值
        );
//        $file = 'people.txt';
//        $log = json_encode($this->publicParam);
//        file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
        file_put_contents("/tmp/publicaliyun.log","aliyun--".date("Y-m-d H:i:s",time()).":".json_encode($this->publicParam)."".PHP_EOL,FILE_APPEND);
    }

    /**
     * 转码
     * @param $string
     * @return mixed|string
     */
    public function percentEncode($string)
    {
        $result = urlencode($string);
        $result = str_replace(['+', '*'], ['%20', '%2A'], $result);
        $result = preg_replace('/%7E/', '~', $result);
        return $result;
//        $result = urlencode($string);
//        $result = preg_replace('/\+/', '%20', $result);
//        $result = preg_replace('/\*/', '%2A', $result);
//        $result = preg_replace('/%7E/', '~', $result);
//        return $result;
    }

    /**
     * 计算签名
     * @param $method
     * @param $parameters
     * @return string
     */
    public function computeSignature($method, $parameters)
    {
        ksort($parameters);
        $canonicalized = '';
        foreach ($parameters as $key => $value) {
            $canonicalized .= '&' . self::percentEncode($key) . '=' . self::percentEncode($value);
        }

        $string = $method . '&%2F&' . self::percentEncode(substr($canonicalized, 1));
        $accessKeySecret = $this->accessKeySecret.'&';

        return base64_encode(hash_hmac('sha1', $string, $accessKeySecret, true));

//        ksort($parameters);
//        $canonicalizedQueryString = '';
//        foreach ($parameters as $key => $value) {
//            $canonicalizedQueryString .= '&' . $this->percentEncode($key). '=' . $this->percentEncode($value);
//        }
//        $stringToSign = parent::getMethod().'&%2F&' . $this->percentencode(substr($canonicalizedQueryString, 1));
//        $signature = $iSigner->signString($stringToSign, $accessKeySecret."&");
//
//        return $signature;
    }

    /**
     * 获取sts数据
     * @param $method
     * @param $url
     * @param $parameters
     */
    public function getStsInfo($user_id){

        $url = "https://cloudauth.aliyuncs.com/?";
        $TicketId = $this->guid();
        $parameters = [
            'Action' => 'GetVerifyToken',
            'RegionId' => 'cn-hangzhou',
            'Biz'=> 'muayy',
            'TicketId'=> $TicketId,
        ];
        $method = 'POST';
        $param = array_merge($this->publicParam, $parameters);
        $param['Signature'] = $this -> computeSignature($method, $param);
//        $file = 'GetVerifyToken.txt';
//        $log = json_encode($param);
//        file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
        $result = $this->curlRequest($url, $param);
        //将xml转化为json
        $arrayData =  $this->xmlToArr($result);
        file_put_contents("/tmp/getverifytoken.log","aliyun--".date("Y-m-d H:i:s",time()).":".json_encode($arrayData)."".PHP_EOL,FILE_APPEND);
        $arrayData = json_encode($arrayData);
        $result = json_decode($arrayData,true);
        //查询当前用户每天不能超过三次提交
        $where = [
            "user_id" => $user_id,
            "ticket_id" => $TicketId,
            "status_code" => array('gt',2),
        ];
        $end_time = strtotime(date('Y-m-d 00:00:00',time()));
        $sta_time = strtotime(date('Y-m-d 23:59:59',time()));
        $where = array(
            "user_id" => $user_id,
            "creat_time <= '".$sta_time."' and creat_time >= '".$end_time."'",
        );
        $face_count = M('face_detect')->where($where)->count();
        if($face_count>=3){
            E("该用户操作次数过多",2000);
        }
//        var_dump($is_face_detect);die();
        if($result['SUCCESS'] == true){
            //如果当前用户在1800秒之内,那么认证数据不会增加,取出用户最后一条数据
            $face_detect_result  = M('face_detect')->where(array("user_id"=>$user_id))->order('id desc')->find();
            //查询当前用户是否成功认证(该用户没有认证或者该用户认证会话过期操作)
            if(empty($face_detect_result) && (time() - $face_detect_result['creat_time'])<1800 && $face_detect_result['status_code'] !== 1){
                $result['Token'] = $face_detect_result['verify_token'];
                $result['DURATIONSECONDS'] = '1800';
            }else{
                $face_data = [
                    "user_id" => $user_id,
                    "ticket_id" => $TicketId,
                    "creat_time" => time(),
                    "verify_token" => $result['TOKEN'],
                    "end_time" => time() + $result['DURATIONSECONDS'],
                ];
                M('face_detect')->save();
                M('face_detect')->add($face_data);
            }
        }
        return $result;
//        $arrayData =  $this->xmlToArr($result);
//        $arrayData = json_encode($arrayData);
//        $result = json_decode($arrayData,true);
    }
    /**
     * 查询指定业务场景下一个认证ID的认证状态
     * @param $method
     * @param $url
     * @param $parameters
     */
    public function getStatusTic($TicketId){
        $url = "https://cloudauth.aliyuncs.com/?";
        $parameters = [
            'Action' => 'GetStatus',
            'RegionId' => 'cn-hangzhou',
            'Biz'=> 'muayy',
            'TicketId'=> $TicketId,
        ];

        $method = 'POST';
        $param = array_merge($this->publicParam, $parameters);
        $param['Signature'] = $this -> computeSignature($method, $param);

//        $file = 'getstatus.txt';
//        $log = json_encode($param);
//        file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
         $result = $this->curlRequest($url, $param);
         $arrayData=  $this->xmlToArr($result);
        file_put_contents("/tmp/getstatus.log","aliyun--".date("Y-m-d H:i:s",time()).":".json_encode($arrayData)."".PHP_EOL,FILE_APPEND);
         $arrayData = json_encode($arrayData);
         return json_decode($arrayData,true);
    }

    /**
     * 获取认证资料
     * @param $method
     * @param $url
     * @param $parameters
     */
    public function getDetail($TicketId){
        $url = "https://cloudauth.aliyuncs.com/?";
        $parameters = [
            'Action' => 'GetMaterials',
            'RegionId' => 'cn-hangzhou',
            'Biz'=> 'muayy',
            'TicketId'=> $TicketId,
        ];

        $method = 'POST';
        $param = array_merge($this->publicParam, $parameters);
        $param['Signature'] = $this -> computeSignature($method, $param);

//        $file = 'getMaterials.txt';
//        $log = json_encode($param);
//        file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
        $result = $this->curlRequest($url, $param);
        $arrayData=  $this->xmlToArr($result);
        file_put_contents("/tmp/getMaterials.log","aliyun--".date("Y-m-d H:i:s",time()).":".json_encode($arrayData)."".PHP_EOL,FILE_APPEND);
        $arrayData = json_encode($arrayData);
        return json_decode($arrayData,true);
    }

    public function guid(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
            return $uuid;
        }
    }
    /**
     * XML转数组
     * 数组格式 array('大写xml的tag'	=>	'xml的value');
     * 数组所有键为大写！！！-----重要！
     */
    private function xmlToArr($xml)
    {
        $parser = xml_parser_create();
        xml_parse_into_struct($parser, $xml, $data, $index);
        $arr = array();
        foreach ($data as $key => $value) {
            $arr[$value['tag']] = $value['value'];
        }
        return $arr;
    }

    private function curlRequest($url,$data = ''){
        $ch = curl_init();
        $params[CURLOPT_URL] = $url;    //请求url地址
//         var_dump($params[CURLOPT_URL]);die();
        $params[CURLOPT_HEADER] = false; //是否返回响应头信息
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_FOLLOWLOCATION] = true; //是否重定向
        $params[CURLOPT_TIMEOUT] = 30; //超时时间
        if(!empty($data)){
            $params[CURLOPT_POST] = true;
            $params[CURLOPT_POSTFIELDS] = $data;
        }
        $params[CURLOPT_SSL_VERIFYPEER] = false;//请求https时设置,还有其他解决方案
        $params[CURLOPT_SSL_VERIFYHOST] = false;//请求https时,其他方案查看其他博文
        curl_setopt_array($ch, $params); //传入curl参数
        $content = curl_exec($ch); //执行
        curl_close($ch); //关闭连接
        //将数据双引号转化为单引号
        return $content;
    }




    /**
     * get请求
     */
    private function httpGet($url, $param){
        $url = $url.'?'.http_build_query($param);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true) ;
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, true) ;
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($curl);
        curl_close($curl);
        var_dump(json_decode($data,true));die();
        return json_decode($data, true);
    }
}
