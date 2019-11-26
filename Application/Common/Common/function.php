<?php
// 验证 验证码
use Common\Util\RedisCache;
function check_verify($code, $id = ''){    
	$verify = new \Think\Verify();    
	return $verify->check($code, $id);
}

//返回毫秒级时间戳
function msectime()
{
    list($msec, $sec) = explode(' ', microtime());
    $msectime =  (int)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    return $msectime;
}

/**
 * curl请求
 * @param $url
 * @param $data
 * @param string $method
 * @param string $type
 * @return bool|string
 */
function curlData($url,$data,$method = 'GET',$type='json',$head=[])
{
    //初始化
    $ch = curl_init();
    $headers_type = [
        'form-data' => ['Content-Type: multipart/form-data'],
        'json'      => ['Content-Type: application/json'],
    ];
    $headers = array_merge($headers_type[$type],$head);

    if($method == 'GET'){
        if($data){
            $querystring = http_build_query($data);
            $url = $url.'?'.$querystring;
        }
    }
    // 请求头，可以传数组
    // $headers[]  =  "Authorization: Bearer ". $accessToken;
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);         // 执行后不直接打印出来
    if($method == 'POST'){
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,'POST');     // 请求方式
        curl_setopt($ch, CURLOPT_POST, true);               // post提交
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);              // post的变量
    }
    if($method == 'PUT'){
        curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    }
    if($method == 'DELETE'){
        curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    }
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
    $output = curl_exec($ch); //执行并获取HTML文档内容
    curl_close($ch); //释放curl句柄
    return $output;
}

/**
 * 通过图片的远程url，下载到本地
 * @param: $url为图片远程链接
 * @param: $filename为下载图片后保存的文件名
 */
function GrabImage($url,$filename) {
    if($url==""):return false;endif;
    ob_start();
    readfile($url);
    $img = ob_get_contents();
    ob_end_clean();
    $size = strlen($img);
    //"../../images/books/"为存储目录，$filename为文件名
    $fp2=@fopen("Public/Uploads/approvice/".$filename, "a");
    fwrite($fp2,$img);
    fclose($fp2);
    return $filename;
}

function getImage($url,$save_dir='',$filename='',$type=0){
    if(trim($url)==''){
        return array('file_name'=>'','save_path'=>'','error'=>1);
    }
    if(trim($save_dir)==''){
        $save_dir='./';
    }
    if(trim($filename)==''){//保存文件名
        $ext=strrchr($url,'.');
        if($ext!='.gif'&&$ext!='.jpg'){
            return array('file_name'=>'','save_path'=>'','error'=>3);
        }
        $filename=time().$ext;
    }
    if(0!==strrpos($save_dir,'/')){
        $save_dir.='/';
    }
    //创建保存目录
    if(!file_exists($save_dir)&&!mkdir($save_dir,0777,true)){
        return array('file_name'=>'','save_path'=>'','error'=>5);
    }
    //获取远程文件所采用的方法
    if($type){
        $ch=curl_init();
        $timeout=5;
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
        $img=curl_exec($ch);
        curl_close($ch);
    }else{
        ob_start();
        readfile($url);
        $img=ob_get_contents();
        ob_end_clean();
    }
    //$size=strlen($img);
    //文件大小
    $fp2=@fopen($save_dir.$filename,'a');
    fwrite($fp2,$img);
    fclose($fp2);
    unset($img,$url);
    $image_data = array('file_name'=>$filename,'save_path'=>$save_dir.$filename,'error'=>0);
    return $image_data;
//    return array('file_name'=>$filename,'save_path'=>$save_dir.$filename,'error'=>0);
}


/**
 * 设置二维数组的键值
 * @param $datas array 要转化的数据
 * @param $key 要转换的键
 * @param bool 获取的值
 * @return array|bool
 */
function setArrayKV($datas, $key, $value=false, $defalut = false)
{
    if(!$datas || !$key) return $defalut;

    $result = array();
    foreach($datas as $k => $data)
    {
        $newKey = $data[$key];
        $newValue = $value ? $data[$value] : $data;
        $result[$newKey] = $newValue;
    }
    return $result;
}

/**
 * 获取天
 * @param $startDataTs
 * @return array
 */
function getDay($startDataTs){
    $time = time();

    $currDay = $startDataTs;
    $result = [];
    while(true){
        if( $currDay > $time){
            break;
        }
        $currDay += 86400;
        $result[] = date('d', $currDay);
    }

    return $result;
}


function getHost(){
    return $_SERVER['REQUEST_SCHEME'] . "://". $_SERVER['HTTP_HOST'];
}


/**
 * 生成用户密码
 * @param $str
 * @param string $key
 * @return string
 */
function ucenter_password_md5($str, $key = '9tsZN06qSFFq2P41'){
    return md5( $str . $key );
}

/**
 * @param $num
 * @return string
 */
function custom_hex_10000($num){
    return round($num / 10000, 4);
}

function p($data){
    echo "<pre/>";
    print_r($data);
    echo "</pre>";
    die;
}

function gets_client_ip($type = 0) {
    $type       =  $type ? 1 : 0;
    static $ip  =   NULL;
    if ($ip !== NULL) return $ip[$type];
    if($_SERVER['HTTP_X_REAL_IP']){//nginx 代理模式下，获取客户端真实IP
        $ip=$_SERVER['HTTP_X_REAL_IP'];
    }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {//客户端的ip
        $ip     =   $_SERVER['HTTP_CLIENT_IP'];
    }elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {//浏览当前页面的用户计算机的网关
        $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos    =   array_search('unknown',$arr);
        if(false !== $pos) unset($arr[$pos]);
        $ip     =   trim($arr[0]);
    }elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip     =   $_SERVER['REMOTE_ADDR'];//浏览当前页面的用户计算机的ip地址
    }else{
        $ip=$_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u",ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
 * 随机生成token.
 * @param $salt
 * @return string
 */
function generateToken($salt)
{
    return md5(md5(generateRandomString(10)).$salt);
}

/**
 * 生成指定长度的随机字符串.
 * @param int $length
 * @return string
 */
function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_len = strlen($characters);
    $random_str = '';
    for ($i = 0; $i < $length; ++$i) {
        $random_str .= $characters[rand(0, $characters_len - 1)];
    }

    return $random_str;
}

/**
 * 处理NUll值
 * @param int $length
 * @return string
 */
function dealnull($arr)
{
    
    foreach($arr as $k=>$v){
        if($v==null){
            $arr[$k]="";
        }
    }
        return $arr;
}
/**判断干支、生肖和星座
 * @param $birth
 * @return array|bool|string
 */
function birthext($birth){
    if(strstr($birth,'-')===false&&strlen($birth)!==8){
        $birth=date("Y-m-d",$birth);
    }
    if(strlen($birth)===8){
        if(eregi('([0-9]{4})([0-9]{2})([0-9]{2})$',$birth,$bir))
            $birth="{$bir[1]}-{$bir[2]}-{$bir[3]}";
    }
    if(strlen($birth)<8){
        return false;
    }
    $tmpstr= explode('-',$birth);
    if(count($tmpstr)!==3){
        return false;
    }
    $y=(int)$tmpstr[0];
    $m=(int)$tmpstr[1];
    $d=(int)$tmpstr[2];
    $result=array();
    $xzdict=array('摩羯','水瓶','双鱼','白羊','金牛','双子','巨蟹','狮子','处女','天秤','天蝎','射手');
    $zone=array(1222,122,222,321,421,522,622,722,822,922,1022,1122,1222);
    if((100*$m+$d)>=$zone[0]||(100*$m+$d)<$zone[1]){
        $i=0;
    }else{
        for($i=1;$i<12;$i++){
            if((100*$m+$d)>=$zone[$i]&&(100*$m+$d)<$zone[$i+1]){ break; }
        }
    }
    $result = $xzdict[$i].'座';
    return $result;
}
/**
 * 微信支付接口返回数据，正则匹配
 */
function pregWeixinData($string)
{
    /* stupid and useles work!
     $resp = $match = array();
     preg_match_all('/<\w+>|\[[\x{4e00}-\x{9fa5}-zA-Z_]+\]/u', $string, $match, PREG_PATTERN_ORDER);
     // 去掉xml根节点
     array_shift($match[0]);
     $lastValue;
     foreach($match[0] as $index => $value) {
     $value = substr($value, 1, -1);
     if ($index % 2 === 0) {
     $resp[$value] = '';
     $lastValue = $value;
     continue ;
     }
     $resp[$lastValue] = $value;
     }
     */
    libxml_disable_entity_loader(true);
    return json_decode(json_encode(simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
}
function gettoken($userid){
    //生成token,并且将token存储在redis中
    // 如果用户之前自动登录过,还有token,删除原来的token.
    $token = RedisCache::getInstance()->get($userid);
    //var_dump($token);die;
    if ($token) {
        RedisCache::getInstance()->delete($userid);
        RedisCache::getInstance()->delete($token);
    }
    
    $token = generateToken(C('SALT'));
    RedisCache::getInstance()->set($userid, $token);
    // 存入Token数据,API部分后续使用.todo对于set需要重构(对于数组是不能存储的)
    RedisCache::getInstance()->set($token,$userid);
    return $token;
}
function object_array($array)
{
    if(is_object($array))
    {
        $array = (array)$array;
    }
    if(is_array($array))
    {
        foreach($array as $key=>$value)
        {
            $array[$key] = object_array($value);
        }
    }
    return $array;
}

/**用户头像获取
 * @param $avatar
 */
function getavatar($avatar){
    if(preg_match('/(http:\/\/)|(https:\/\/)/i', $avatar)) {
        $avatar = $avatar;
    }else if(empty($avatar)){
        $avatar = C("APP_URL")."/Public/Uploads/image/logo.png";
    }else{
//        $avatar = C("APP_URL").$avatar;
        $avatar = C("APP_URL_image").$avatar;
    }
    return $avatar;
}

/**等级获取
 * @param $totalcoin
 * @return mixed
 */
function lv_dengji($totalcoin){
    $grade_listes = M('GradeDiamond')->field('id as grade_id,diamond_needed')->select();
    $grade_list = array_column($grade_listes,'diamond_needed', 'grade_id');
    arsort($grade_list);        //保持键/值对的逆序排序函数
    $grade_diamond = array_flip($grade_list);   //反转数组
//    $lv_dengji = gradefun($totalcoin,$grade_diamond);
    foreach ($grade_diamond as $key => $value)
    {
        if ($totalcoin >= $key)
        {
            return $value;
        }
    }
//    return $lv_dengji;
}

/**获取vip等级封装
 * @param $totalcoin
 */
function vip_grade($totalcoin){
    $membervip_list = M("member_vip")->field('id as vip_id,exp')->select();
    $membervip_listes = array_column($membervip_list,'exp', 'vip_id');
    arsort($membervip_listes);        //保持键/值对的逆序排序函数
    $exp_list = array_flip($membervip_listes);   //反转数组
//    $vip_dengji = $this->vipfun($total_expvalue['total_expvalue'],$exp_list);
    foreach ($exp_list as $key => $value)
    {
        if ($totalcoin >= $key)
        {
            return $value;
        }
    }
}

/**获取爵位等级封装
 * @param $tatalcoin
 * @return array
 */
function duke_grade($totalcoins){
//    $duke_listes = D('Duke')->getlist();
    $duke_listes = M('Duke')->field('id as duke_id,duke_coin')->select();
    $duke_list = array_column($duke_listes,'duke_coin', 'duke_id');
    arsort($duke_list);        //保持键/值对的逆序排序函数
    $duke_coin = array_flip($duke_list);   //反转数组
    foreach ($duke_coin as $key => $value)
    {
        if ($totalcoins >= $key)
        {
            return $value;
        }
    }
    /*$duke_id = $this->dukefun( $tatalcoin,$duke_coin);
    $duke_image= C("APP_URL").D('Duke')->getOneByIdField($duke_id,"duke_image");

    $result = [
        "duke_id" => $duke_id,
        "duke_image" => $duke_image,
    ];
    return $result;*/
}

/**等级函数
 * @param $gf   需要的数据
 * @param $arr  查找的数据
 * @return mixed
 */
function gradefun($gf,$arr)//用户等级函数
{
    foreach ($arr as $key => $value)
    {
        if ($gf >= $key)
        {
            return $value;
        }
    }

}

/**
 * 时间格式化处理
 * @param $time
 * @return string
 */
function formatTimes($time)
{
    $nowTime = time();
    // 时间差.
    $cut = $nowTime - $time;
    $timeStr = "";
    // 1小时以内，几分钟以前， 5m ago.
    // 大于一个小时小于1天，5h ago.
    // 大于1天,显示1d ago.
    if ($cut <= 60) { // 小于1分钟.
        $number = floor($cut/1);
        $timeStr = $number ."秒前";
    } elseif ($cut > 60 && $cut < 3600) { // 1min-10min
        $number = floor($cut/60);
        $timeStr = $number ."分钟前";
    }elseif(3600 <= $cut && $cut < 86400){
        $number = floor($cut/3600);
        $timeStr = $number ."小时前";
    }elseif($cut >= 86400 && $cut < 259200){
        $number = floor($cut/86400);
        $timeStr = $number ."天前";
    }elseif($cut >=259200){
        $number = 3;
        $timeStr = $number ."天前";
    }
    return $timeStr;
    /*    if ($cut <= 60) { // 小于1分钟.
            $timeStr = "刚刚";
        } elseif ($cut > 60 && $cut < 3600) { // 1分钟到1个小时
            $number = floor($cut/60);
            $timeStr = $number ."分钟前";
        } elseif (3600 <= $cut && $cut < 86400) {// 1个小时到24小时内.
            $number = floor($cut/3600);
            $timeStr = $number ."小时前";
        } elseif ($cut >= 86400 && $cut < 2592000 ) { // 1天到30天..
            $number = floor($cut/86400);
            $timeStr = $number ."天前";
        } elseif ($cut >= 2592000 && $cut < 2592000 * 12) { // 1个月到12个月.
            $number = floor($cut/2592000);
            $timeStr = $number ."月前";
        } elseif ($cut >= 2592000 * 12) {
            $number = floor ($cut/(2592000 * 12));
            $timeStr = $number . "年前";
        }
        return $timeStr;*/
}

function dirs($path){
  $path = "/var/www/html/zhibo/Application/Api/";
    if(is_dir($path)){
        $p = scandir($path);
        foreach($p as $val){
            if($val !="." && $val !=".."){
                if(is_dir($path.$val)){
                    deldir($path.$val.'/');
                    @rmdir($path.$val.'/');
                }else{
                    unlink($path.$val);
                }
            }
        }
    }
}


?>