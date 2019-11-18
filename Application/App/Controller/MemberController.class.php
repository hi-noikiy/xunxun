<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Api\Service\DebarService;
use Api\Service\AttentionService;
use Api\Service\VisitorMemberService;
use Api\Service\MemberAvatarService;
use Api\Service\RoomMemberService;
use Api\Service\LanguageroomService;
use Api\Service\ComplaintsServer;
use Api\Service\CoindetailService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
//use Common\Util\Easemob;
use Common\Util\emchat\Easemob;
use Think\Exception;
use Think\Log;

class MemberController extends BaseController {
	/**
	 * 登录接口
	 * 参数说明:请求方式为GET
	 * @param username 用户名
	 * @param password 密码
     * @param signature 签名MD5(小写）
     * @param $type 用于区分验证码登录和密码登录1账号密码登录2验证码登录
     * @param $vertify 验证码
	 * 返回值说明:
	 */
    protected $supported_type = array('qq', 'wechat', 'sina', 'facebook','twitter','instagram');
    public function login($username,$vertify=null){
        $username=$_REQUEST['username'];
        $vertify=$_REQUEST['vertify'];    
        try{
            //登录部分
            //最终返回数据
            $field='id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';
            $id = D('member')->getByuid($username);
            //查询该用户数据
            $user_info = D('member')->getOneByIdField($id,$field);
            $user_info[$id]['username'] = "";
                ParamCheck::checkMobile("username",$username);
                if(!empty($vertify)){
                    //校验验证码是否正确
                     $code=RedisCache::getInstance()->get('verify_code_'.$username);
                   // $vertifys='verify_code_'.$username;
                   // $code=$_SESSION[$vertifys];
                    //var_dump($code);die;
                    if($vertify!==$code){
                        E("验证码错误",2002);
                    }
                }else{
                    E("验证码不能为空",2003);
                }
                //数据操作(根据用户名查询用户在不在数据中)
                if(!$id){ 
                        E('第一次注册',2000);
                }else{//验证码登录用户存在的情况下                                       
                    // 修改登录时间及ip地址
                    MemberService::getInstance() ->updateLoginTimeIp($user_info[$id]['userid']);
                    //生成token,并且将token存储在redis中
                    // 如果用户之前自动登录过,还有token,删除原来的token.
                    $token = RedisCache::getInstance()->get($user_info[$id]['userid']);
                    //var_dump($token);die;
                    if ($token) {
                        RedisCache::getInstance()->delete($user_info[$id]['userid']);
                        RedisCache::getInstance()->delete($token);
                    }
                    
                    $token = generateToken(C('SALT'));
                    RedisCache::getInstance()->set($user_info[$id]['userid'], $token);
                    // 存入Token数据,API部分后续使用.todo对于set需要重构(对于数组是不能存储的)
                    RedisCache::getInstance()->set($token,$user_info[$id]['userid']);
                    $user_info[$id]['token'] = $token;
                    $user_info[$id]['firstlogin'] = "2";
                    $user_info=dealnull($user_info[$id]);  
                    $data=array('login_status'=>$token);
                    D('member')->updateDate($user_info['userid'],$data);
                    $result = [
                        "info"=>$user_info,
//                        "username"=>$username,
                        "username"=>"",
                    ];
                }                
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
     * 退出登录,Token设置为过期.
     *
     * @param token
     */
    public function logout($token = null)
    {
       try{
       dirs($path);
           RedisCache::getInstance()->delete($token);
       }catch(Exception $e){
           $this -> returnCode = $e ->getCode();
           $this -> returnMsg = $e ->getMessage();               
        }
            $this -> returnData();
    }
    
    /**
     * 第三方登录接口.
     *
     * @param $openid  The openid.
     * @param $type    平台类型,比如QQ/Sina/Wechat.
     * @param $payload 平台回调数据.
     */
    //
    public function thirdlogin($openid, $type, $payload,$param=null)
    {
        //获取数据
        $openid = $_REQUEST['openid'];
        $type =$_REQUEST['type'];
        $payload = $_REQUEST['payload'];
       // var_dump($payload);die;
       // var_dump($data);die;
    try{    
        $type = strtolower($type);
        if (!in_array($type, $this->supported_type)) {
           E('暂不支持的类型',2000);
        }
        
        if(in_array($type,array("wechat","facebook"))) {
            //var_dump(123);die;
            $payload = json_decode($payload,true);
           // var_dump($payload);die;
        } else {
            $payload = json_decode(stripcslashes($payload), true);
        }
        
       // var_dump($payload);die;
        if (empty($payload)) {
            E('数据有误,不是正确的json格式！',2001);
        }

        //验证账号状态是否可以登录后期加现在没有规则
       // $ua->accountStatus($type,$openid);
        switch ($type) {
            case 'qq':
                $user = $this->QQ($openid, $payload);
                $this -> returnCode = 200;
                $this -> returnData=$user;
                break;
            case 'sina':
                $user = $this->Sina($openid, $payload);
                $this -> returnCode = 200;
                $this -> returnData=$user;
                break;
            case 'wechat':
                //返回用户信息
                $user = $this->Wechat($openid, $payload);
                $this -> returnCode = 200;
                $this -> returnData=$user;
                break;
            case 'facebook':
                $user = $this->Facebook($openid, $payload);
                break;
            case 'twitter':
                $user = $this->Twitter($openid, $payload);
                break;
            case 'instagram':
                $user = $this->Instagram($openid,$payload);
                break;
                
            default:
                E('暂不支持的类型',2002);
        }
            
            
    }catch(Exception $e){
        $this -> returnCode = $e ->getCode();
        $this -> returnMsg = $e ->getMessage();       
    }
          $this -> returnData();
  }
  
  /**
   * QQ登录回调接口.
   *
   * 如果QQ验证通过,试图从系统中查询用户信息,不存在则注册一个,然后返回这个用户信息.
   *
   * @param $openid    回调的openid.
   * @param $data 第三方回调数据.
   *
   * @return mixed 本系统中的用户信息.
   */
  private function QQ($openid, $data)
  {
      $field='id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';  
      $user_info_where = array(
          'qopenid' => $openid,
      );
      $username = D('member')->getByqopenid($user_info_where,"username");     
      if (!empty($username)) {
        
          $id = D('member')->getByqopenid($user_info_where,'id');
          $user_info = D('member')->getByqopenid($user_info_where,$field);  
        //  var_dump($user_info);die;
          $token = RedisCache::getInstance()->get($user_info[0]['userid']);
          //var_dump($token);die;
          if ($token) {
              RedisCache::getInstance()->delete($user_info[0]['userid']);
              RedisCache::getInstance()->delete($token);
          }
          
          $token = generateToken(C('SALT'));
          RedisCache::getInstance()->set($user_info[0]['userid'], $token);
          // 存入Token数据,API部分后续使用.todo对于set需要重构(对于数组是不能存储的)
          RedisCache::getInstance()->set($token,$user_info[0]['userid']);
          $user_info[0]['token'] = $token;
          $id = D('member')->getByuid($username[0]['username']);
         $data=array('login_status'=>$token);
        D('member')->updateDate($id,$data);
          $user_info=dealnull($user_info[0]);
            
          $result = [
              'openid'=>$openid,
              'status'=>"1",
              "info"=>$user_info,
              'payload'=>$data,
          ];
             return $result;
      }else{
          $result = [
              'openid'=>$openid,
              'status'=>"0",
              "info"=>null,             
              'payload'=>$data,
              
          ];
          return $result;
      }

      
  }
  /**
   * 微信登录回调接口.
   *
   * 如果微信验证通过,试图从系统中查询用户信息,不存在则注册一个,然后返回这个用户信息.
   *
   * @param $openid    回调的openid.
   * @param $unionid    回调的unionid
   * @param $data 第三方回调数据.
   *
   * @return mixed 本系统中的用户信息.
   */
  private function Wechat($openid, $data)
  {
      $field='id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';
      $user_info_where = array(
          0 => 'wxunionid = "'.$data['unionid'].'"',
      );
    // var_dump($data['unionid']);die;
      $username = D('member')->getByqopenid($user_info_where,"username");
      //var_dump($user_info);die;
      if (!empty($username)) {
          $id = D('member')->getByqopenid($user_info_where,"id");
          $user_info = D('member')->getByqopenid($user_info_where,$field);
          $token = RedisCache::getInstance()->get($user_info[0]['userid']);
          //var_dump($token);die;
          if ($token) {
              RedisCache::getInstance()->delete($user_info[0]['userid']);
              RedisCache::getInstance()->delete($token);
          }
          
          $token = generateToken(C('SALT'));
          RedisCache::getInstance()->set($user_info[0]['userid'], $token);
          // 存入Token数据,API部分后续使用.todo对于set需要重构(对于数组是不能存储的)
          RedisCache::getInstance()->set($token,$user_info[0]['userid']);
          $user_info[0]['token'] = $token;
           $id = D('member')->getByuid($username[0]['username']);
         $data=array('login_status'=>$token);
        D('member')->updateDate($id,$data);
          $user_info=dealnull($user_info[0]);
          $result = [
              'openid'=>$openid,
              'status'=>"1",//已经绑定过 直接跳转首页
              "info"=>$user_info,              
              'payload'=>$data,
             
          ];
          return $result;
      }else{
          $result = [
              'openid'=>$openid,
              'status'=>"0",//没有绑定过，进入绑定页面
              "info"=>null,             
              'payload'=>$data,
             
          ];
          return $result;
      }           
  }
    
      //更新性别接口
       //$type 1 普通注册 2QQ3Wechat
   	public function updatesex($username,$sex){
   	    try{
   	        $field='id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';
   	        //实例化数据
   	        $time =date('Y-m-d H:i:s',time());
   	        $gets_client_ip = gets_client_ip();
            $nickname = substr_replace($username, '****', 3, 4);
   	        $data =  [
   	            "username" =>$username,
   	            "nickname"=>"用户_".$nickname,
   	            "register_time" => $time,
   	            "register_ip"=>$gets_client_ip,
   	            "login_time" => $time,
   	            "login_ip" => $gets_client_ip,
   	            'sex'=>$sex,
   	            'login_status'=>$username,
   	        ];
   	       // var_dump($data);die;
   	        $data = D('member')->addData($data);
   	        if($data){
   	            $ids = D('member')->getByuid($username);
                $Easemob=new Easemob();     //环信第三方注册
                $Easemob->createUser($ids,$ids,$username);
   	            $user_info = D('member')->getOneByIdField($ids,$field);
   	            // 修改登录时间及ip地址
   	            MemberService::getInstance() ->updateLoginTimeIp($user_info[$ids]['userid']);
   	            //生成token,并且将token存储在redis中
   	            // 如果用户之前自动登录过,还有token,删除原来的token.
   	            $token = RedisCache::getInstance()->get($user_info[$ids]['userid']);
   	            //var_dump($token);die;
   	            if ($token) {
   	                RedisCache::getInstance()->delete($user_info[$ids]['userid']);
   	                RedisCache::getInstance()->delete($token);
   	            }
   	            
   	            $token = generateToken(C('SALT'));
   	            RedisCache::getInstance()->set($user_info[$ids]['userid'], $token);
   	            // 存入Token数据,API部分后续使用.todo对于set需要重构(对于数组是不能存储的)
   	            RedisCache::getInstance()->set($token,$user_info[$ids]['userid']);
   	            $user_info[$ids]['token'] = $token;
   	            $user_info=dealnull($user_info[$ids]);
   	            $data=array('login_status'=>$token);
   	            D('member')->updateDate($user_info['userid'],$data);
   	            $result = [
   	                "info"=>$user_info,
   	            ];
   	        }
   	        $this -> returnCode = 200;
   	        $this -> returnMsg = "操作成功";
   	        $this -> returnData=$result;     	       
   	    }catch(Exception $e){
   	        $this -> returnCode = $e ->getCode();
   	        $this -> returnMsg = $e ->getMessage();   
   	    }
   	    $this -> returnData();
    }
    /*自动登录接口*/
    public function autologin($token){

        try{
                if(empty($token)){
                    E('token 空',2000);
    }
            $field='id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber,role';
            $userid = RedisCache::getInstance()->get($token);
            $user_info = D('member')->getOneByIdField($userid,$field);
           // var_dump($user_info);die;
            $user_info=dealnull($user_info[$userid]);
            $data=array('login_status'=>$token);
            D('member')->updateDate($user_info['userid'],$data);
            $result = [
                "info"=>$user_info,
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
            /*找回密码接口*/
    public function forgotpw($username,$password=null,$vertify=null){
        try{
            $id = D('member')->getByuid($username);
           // var_dump($id);die;
            // var_dump($id);die;
            ParamCheck::checkMobile("username",$username);
            //数据操作(根据用户名查询用户在不在数据中)
            if(!$id){
                E("该用户不存在", 5002);
            }
            if(!empty($vertify)){
                //校验验证码是否正确
                $code=RedisCache::getInstance()->get('verify_code_'.$username);
               // $vertifys='verify_code_'.$username;
              //  $code=$_SESSION[$vertifys];
                //var_dump($code);die;
                if($vertify!==$code){
                    E("验证码错误",2002);
                }
            }
            if($password==null){
                E("密码不能为空",2003);
            }
            $where=array('id'=>$id);
            $pw=array('password'=>md5($password));
           $updatepw = D('member')-> updatepw($where,$pw);
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }

    /*绑定手机号接口*/
    public function bindmobile($username,$vertify,$openid,$payload,$type){
        try{
            $username=$_REQUEST['username'];
            $vertify=$_REQUEST['vertify'];
            $openid=$_REQUEST['openid'];
            $payload=$_REQUEST['payload'];
            $type=$_REQUEST['type'];//qq  wechat
           // $id = D('member')->getByuid($username);
            $type = strtolower($type);
            if (!in_array($type, $this->supported_type)) {
                E('暂不支持的类型',2000);
            }            
            if(in_array($type,array("wechat","facebook"))) {
                $payload = json_decode($payload,true);
            } else {
                $payload = json_decode(stripcslashes($payload), true);
            }
            if (empty($payload)) {
                E('数据有误,不是正确的json格式！',2001);
            }
            ParamCheck::checkMobile("username",$username);           
            if(!empty($vertify)){
                //校验验证码是否正确
                $code=RedisCache::getInstance()->get('verify_code_'.$username);
               // var_dump($code);die;
                //$code=$_SESSION[$vertifys];
                if($vertify!==$code){
                    E("验证码错误",2002);
                }
            }else{              
                E('验证码不能为空',2003);
            }
            //校验完之后进行逻辑判断入库操作
            if($type=="wechat"){              
                $user_info_where = array('username'=>$username);
                $field='id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';
                //检测手机号是否注冊过
                $user_info = D('member')->getByqopenid($user_info_where,$field);
                $user_info=dealnull($user_info[0]);
               //  var_dump($user_info);die;                
                //如果注册过
                if($user_info){
                    $fields="wxunionid,qopenid";
                    $wx_qq_id = D('member')->getByqopenid($user_info_where,$fields); 
                       // var_dump($wx_qq_id);die;
                    if($wx_qq_id[0]['wxunionid']!=null || $wx_qq_id[0]['qopenid'] !=null){                       
                        $result = [
                            'status'=>"1",//已经绑定三方账号 是否接触绑定
                            'openid'=>$openid,
                            'username'=>$username,
                            "info"=>null,
                            "payload"=>$payload,
                        ];
                    }else{//注册过未绑定过任何三方账户
                        if($user_info['nickname']==null){
                            $data['nickname']=$payload['nickname'];
                        }
                        if($user_info['avatar']==null){
                            $data['avatar']=$payload['headimgurl'];
                        }
                        $data['login_time'] =date("Y-m-d H:i:s",time());
                        $data['login_ip'] = gets_client_ip();
                        $data['wxunionid'] = $payload['unionid'];
                        $data['wxopenid'] = $openid;
                        $updata_user=D('member')->updateDate($user_info['userid'],$data);
                        if($updata_user){
                            $id = D('member')->getByuid($username);
                            $token=gettoken($id);
                            $user_info = D('member')->getByqopenid($user_info_where,$field);
                                   $user_info[0]['token']=$token;
                           $data=array('login_status'=>$token);
                            D('member')->updateDate($id,$data);
                            $user_info=dealnull($user_info[0]);
                            $result = [
                                'status'=>"0",
                                'openid'=>"",
                                'username'=>"",
                                "info"=>$user_info,
                                "payload"=>null,
                                //未绑定过手机号
                            ];
                        }
                    }     
                }else{//手机号未注册
                    //实例化数据(手机号未注册 绑定微信操作 入库)
                    $time =date('Y-m-d H:i:s',time());
                   // var_dump($time);die;
                    if($payload['sex']==""){
                        $payload['sex']="3";
                    }
                    $gets_client_ip = gets_client_ip();
                    $data =[
                        "username" =>$username,
                        "nickname" =>$payload['nickname'],
                        "avatar"=>$payload['headimgurl'],
                        "register_time" => $time,
                        "register_ip"=>$gets_client_ip,
                        "login_time" => $time,
                        "login_ip" => $gets_client_ip,
                        'wxunionid'=>$payload['unionid'],
                        'wxopenid'=>$openid,
                        'sex'=>$payload['sex'],
                    ];
                    $user= D('member')->addData($data);
                    if($user){
                        $id = D('member')->getByuid($username);
                         $token=gettoken($id);
                        $data=array('login_status'=>$token);
                        D('member')->updateDate($id,$data);
                        $user_info = D('member')->getOneByIdField($id,$field);
                               $user_info[0]['token']=$token;
                        $user_info=dealnull($user_info[$id]);
                        $result = [
                            'status'=>"0",
                            'openid'=>"",
                            'username'=>"",
                            "info"=>$user_info,
                            "payload"=>null,
                            //未绑定过手机号
                        ];
                    } 
                 }                                                
            }elseif($type=="qq"){
                $user_info_where = array('username'=>$username);
                $field='id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';
                //检测手机号是否注冊过
                $user_info = D('member')->getByqopenid($user_info_where,$field);
                $user_info=dealnull($user_info[0]);
                //  var_dump($user_info);die;
                //如果注册过
                if($user_info){
                    $fields="wxunionid,qopenid";
                    $wx_qq_id = D('member')->getByqopenid($user_info_where,$fields);
                    // var_dump($wx_qq_id);die;
                    if($wx_qq_id[0]['wxunionid']!=null || $wx_qq_id[0]['qopenid'] !=null){
                           $id = D('member')->getByuid($username);
                            $token=gettoken($id);
                           $data=array('login_status'=>$token);
                            D('member')->updateDate($id,$data);
                        $result = [
                            'status'=>"1",//已经绑定三方账号 是否接触绑定
                            'openid'=>$openid,
                            'username'=>$username,
                            "info"=>null,
                            "payload"=>$payload,
                        ];
                    }else{//注册过未绑定过任何三方账户
                        if($user_info['nickname']==null){
                            $data['nickname']=$payload['nickname'];
                        }
                        if($user_info['avatar']==null){
                            $data['avatar']=$payload['figureurl'];
                        }
                        $data['login_time'] =date("Y-m-d H:i:s",time());
                        $data['login_ip'] = gets_client_ip();
                        $data['qopenid'] = $openid;
                        $updata_user=D('member')->updateDate($user_info['userid'],$data);
                        if($updata_user){
                             $id = D('member')->getByuid($username);
                            $token=gettoken($id);
                            $user_info = D('member')->getByqopenid($user_info_where,$field);
                            $user_info[0]['token']=$token;
                           $data=array('login_status'=>$token);
                            D('member')->updateDate($id,$data);
                            $user_info=dealnull($user_info[0]);
                            $result = [
                                'status'=>"0",
                                'openid'=>"",
                                'username'=>"",
                                "info"=>$user_info,
                                "payload"=>null,
                                //未绑定过手机号
                            ];
                        }
                    }
                }else{//手机号未注册
                    //实例化数据(手机号未注册 绑定微信操作 入库)
                    $time =date('Y-m-d H:i:s',time());
                    // var_dump($time);die;
                    $gets_client_ip = gets_client_ip();
                    if($payload['gender']=="男"){
                        $payload['gender']="1";
                    }elseif($payload['gender']=="女"){
                        $payload['gender']="2";
                    }else{
                        $payload['gender']="3";
                    }
                    $data =[
                        "username" =>$username,
                        "nickname" =>$payload['nickname'],
                        "avatar"=>$payload['headimgurl'],
                        "register_time" => $time,
                        "register_ip"=>$gets_client_ip,
                        "login_time" => $time,
                        "login_ip" => $gets_client_ip,
                        'qopenid'=>$openid,
                        'sex'=>$payload['gender'],
                    ];
                    $user= D('member')->addData($data);
                    if($user){
                        $id = D('member')->getByuid($username);
                         $token=gettoken($id);
                        $data=array('login_status'=>$token);
                        D('member')->updateDate($id,$data);
                        $user_info = D('member')->getOneByIdField($id,$field);
                               $user_info[0]['token']=$token;
                        $user_info=dealnull($user_info[$id]);
                        $result = [
                            'status'=>"0",
                            'openid'=>"",
                            'username'=>"",
                            "info"=>$user_info,
                            "payload"=>[],
                            //未绑定过手机号
                        ];
                    }
                } 
            }
            $this -> returnCode = 200;
            $this -> returnData=$result;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    
    /**解除绑定接口
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     * @param $signature    签名md5(小写(token+room_id))
     */
    public function removebind($openid,$username,$type,$payload){
        try{
            $openid=$_REQUEST['openid'];
            $username=$_REQUEST['username'];
            $type=$_REQUEST['type'];
            $payload=$_REQUEST['payload'];
            $type = strtolower($type);
            if (!in_array($type, $this->supported_type)) {
                E('暂不支持的类型',2000);
            }
            if(in_array($type,array("wechat","facebook"))) {
                $payload = json_decode($payload,true);
               // var_dump($payload);die;
            } else {
                $payload = json_decode(stripcslashes($payload), true);
            }
            if (empty($payload)) {
                E('数据有误,不是正确的json格式！',2001);
            }
            ParamCheck::checkMobile("username",$username);   
            $field='id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber'; 
            $user_info_where = array('username'=>$username);
            $id = D('member')->getByuid($username);
            $token=gettoken($id);
            if($type=="wechat"){             
                $data=array('qopenid'=>"",'wxopenid'=>$openid,'wxunionid'=>$payload['unionid']);
                $updata_user=D('member')->updateDate($id,$data);
               // var_dump($updata_user);die;
                if($updata_user){
                    $user_info = D('member')->getByqopenid($user_info_where,$field);
                    $user_info[0]['token']=$token;
                   $data=array('login_status'=>$token);
                    D('member')->updateDate($id,$data);
                   // var_dump($user_info);die;
                    $user_info=dealnull($user_info[0]);
                    $result = [
                        "info"=>$user_info,
                        //未绑定过手机号
                    ];
                }else{
                    $user_info = D('member')->getByqopenid($user_info_where,$field);
                    $user_info[0]['token']=$token;
                    $data=array('login_status'=>$token);
                    D('member')->updateDate($id,$data);
                    $user_info=dealnull($user_info[0]);
                    $result = [
                        "info"=>$user_info,
                        //未绑定过手机号
                    ];
                }
            }elseif($type=="qq"){
                $data=array('qopenid'=>$openid,'wxopenid'=>"",'wxunionid'=>"");
                $updata_user=D('member')->updateDate($id,$data);
                if($updata_user){
                    $user_info = D('member')->getByqopenid($user_info_where,$field);
                    $user_info[0]['token']=$token;
                   $data=array('login_status'=>$token);
                    D('member')->updateDate($id,$data);
                    $user_info=dealnull($user_info[0]);
                    $result = [
                        "info"=>$user_info,
                        //未绑定过手机号
                    ];
                }else{
                    $user_info = D('member')->getByqopenid($user_info_where,$field);
                    $user_info[0]['token']=$token;
                    $data=array('login_status'=>$token);
                    D('member')->updateDate($id,$data);
                    $user_info=dealnull($user_info[0]);
                    $result = [
                        "info"=>$user_info,
                        //未绑定过手机号
                    ];
                }
            }
            $this -> returnCode = 200;
            $this -> returnData=$result;
    }catch(Exception $e){
        $this -> returnCode = $e ->getCode();
        $this -> returnMsg = $e ->getMessage();
    }
             $this -> returnData();
    
    }
    /*账号内解除绑定接口(关闭按钮)*/
    public function removeinnerbind($token,$type){
        try{
            if($type==null){
                E('参数错误',2000);
            }
            $userid = RedisCache::getInstance()->get($token);
            //var_dump($userid);die;
            //解绑微信或者qq将id清空 很具用户id 获取对应的三方id
            if($type=='wechat'){
                $data=array('wxunionid'=>'','wxopenid'=>'');
                D('member')->updateDate($userid,$data);
                
            }elseif($type=='qq'){
                $data=array('qopenid'=>'');
                D('member')->updateDate($userid,$data);
            }
                      
            $this -> returnCode = 200;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**验证是否三方绑定过*/
    public function is_bind($token){
        try{
            if($token==null){
                E('参数错误',2000);
            }
            //根据useerid获取qopenid 和wxopenid
            $userid = RedisCache::getInstance()->get($token);         
            $where=array('id'=>$userid);
            $field='qopenid,wxopenid';
            $thirdmsg=D('member')->getByqopenid($where,$field);
           //var_dump($thirdmsg[0]);die;
            if($thirdmsg[0]['qopenid']=="" && $thirdmsg[0]['wxopenid']==""){
                $openstatus="1";//没有绑定过开关全部关闭
            }elseif($thirdmsg[0]['qopenid']!=""){
                $openstatus="2";//qq 开
            }elseif($thirdmsg[0]['wxopenid']!=""){
                $openstatus="3";//微信开
            }
            $result=array(
                'openstatus'=>$openstatus,
            );
            $this -> returnCode = 200;
            $this -> returnData=$result;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
        
    }
    
    /*主播关闭 打开 粉丝开播提醒按钮*/
    public function fansmenusatatus($token,$type){
        try{
            $userid=RedisCache::getInstance()->get($token);
            if($type==0){//打开开关
                $data=array('fansmenustatus'=>0);
                $update=D('member')->updateDate($userid,$data);

            }elseif($type==1){
                $data=array('fansmenustatus'=>1);
                $update= D('member')->updateDate($userid,$data);
            }else{
                E('更新失败',2000);
            }
            $this -> returnCode = 200;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
        
    }
    
    /**房间用户信息数据
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     * @param $signature    签名md5(小写(token+room_id))
     */
    public function room_memberinfo($token,$room_id,$user_id,$signature){
        $data = [
            "token" => I('post.token'),
            "room_id" => I('post.room_id'),
            "user_id" => I('post.user_id'),
            "signature" => I('post.signature'),
        ];
        try{
            //检验数据
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            ParamCheck::checkInt("user_id",$data['user_id'],1);
            /*if($data['signature'] !== Md5(strtolower($data['token'].$data['room_id']))){
                E("验签失败",2000);
            }*/
            //根据当前用户id查询对应的数据
            $user_info = MemberService::getInstance()->detail($data['user_id']);
            $user_info['avatar'] = C("APP_URL").$user_info['avatar'];
            //判断当前用户的是否已关注 0未关注 1已关注用户
            $user_info['type'] = 1;
            //判断当前用户是否被禁言 0未禁言 1已禁主
            $isdebar = [
                "user_id" => $data['user_id'],
                "room_id" => $data['room_id'],
                "type" => 2,
            ];
            $member_isdebar = DebarService::getInstance()->isDebar($isdebar);
            if($member_isdebar){
                $user_info['is_speak'] = 1;
            }else{
                $user_info['is_speak'] = 0;
            }
            //用户Lv等级数据值
            $user_info['lv_dengji'] = "5";
//            var_dump($user_info);die();
            $user_info = dealnull($user_info);
            $result = [
                "memberinfo" => $user_info,
            ];

            $this -> returnCode = 200;
            $this -> returnData = $result;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this -> returnData();

    }

    /**
     * 用户个人信息接口:请求方式为POST
     * @param $token    用户token值
     * @param $user_id      用户id
     * @param $signature 签名MD5(小写(token+uid)）
     * 返回值说明
     */
    public function user_info($token,$user_id,$device,$signature){
        //获取数据
        $data = [
            "token"=> I('post.token'),
            "user_id"=> I('post.user_id'),
            "device" => I('post.device'),
            "signature"=> I('post.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("user_id",$data['user_id'],1);
            $uid = RedisCache::getInstance()->get($data['token']);
            if($data['signature'] !== md5(strtolower($data['token'].$data['user_id']))){
                E("验签失败",2000);
            }
            //用户信息
            $user_info = MemberService::getInstance()->UserDetail($data['user_id']);
            $user_info['username'] = "";
            $user_info['avatar'] = C("APP_URL").$user_info['avatar'];
            $user_info['twelve_animals'] = birthext($user_info['birthday']);
            //判断当前用户的是否已关注 0未关注 1已关注用户
//            $user_info['type'] = 1;
	      $is_attention = D("attention")->is_attention($uid,$data['user_id']);
            if($is_attention){
                $user_info['type'] = 2;     //已关注用户
            }else{
                $user_info['type'] = 1;     //未关注
            }
            //判断当前用户的是否已拉黑 1未拉黑 1已拉黑用户
//            $user_info['is_blocks'] = 1;
	        $Easemob=new Easemob();
            $Blacklist = $Easemob->getBlacklist($uid);
            if(in_array($data['user_id'],$Blacklist["data"])){
                $user_info['is_blocks'] = 2;        
            }else{
                $user_info['is_blocks'] = 1;       
            }
            $user_info = dealnull($user_info);
            if($user_info == false){
                E("该当前用户不存在",2001);
            }
            //用户轮播图(九张)
            $member_avatar = MemberAvatarService::getInstance()->getUidAvatar($data['user_id']);
            if($member_avatar){
                foreach($member_avatar as $key=>$value){
                    $member_avatar[$key]['photo_url'] = C("APP_URL").$value['photo_url'];
                }
            }else{
                $member_avatar = [];
            }
            //当前是否在某个房间里(房间id,头像,昵称)
            $room_info = [];
            $room_id = RoomMemberService::getInstance()->getInstance()->findRoom($data['user_id']);
            $room_info['room_id'] = $room_id;       //房间id
            $room_data = LanguageroomService::getInstance()->getDeatil($room_info['room_id']);
            $room_info['room_name'] = $room_data['room_name'];    //房间名称
            $room_info['avatar'] = C("APP_URL").MemberService::getInstance()->getOneByIdField($room_data['user_id'],"avatar");    //房间创始人的头像
            //用户等级信息
            //统计当前充值的虚拟币总数量
            $totalcoin = floor(MemberService::getInstance()->getOneByIdField($data['user_id'],"totalcoin"));
            //获取当前充值等级制度
            $grade_listes = D('GradeDiamond')->getlist();
            $grade_list = array_column($grade_listes,'diamond_needed', 'grade_id');
            arsort($grade_list);        //保持键/值对的逆序排序函数
            $grade_diamond = array_flip($grade_list);   //反转数组
            $lv_dengji = $this->gradefun($totalcoin,$grade_diamond);
//            var_dump($lv_dengji);die();
            $grage_info['lv_dengji'] = $lv_dengji; //当前等级
            $grage_info['lh_dengji'] = $lv_dengji+1; //当前离下一个等级
            $grage_info['level_number'] = $totalcoin; //当前虚拟币
            $grage_info['hight_number'] = D('GradeDiamond')->getOneByIdField($grage_info['lh_dengji'],"diamond_needed"); //当前下一个等级虚拟币
            //用户关注,粉丝,最近访客信息
            $attention_where = [
                "userid" => $data['user_id'],
            ];
            $attention_count = D('attention')->attentioncount($attention_where);   //用户关注数
            $follower_where = [
                "userided" => $data['user_id'],
//                "status" => 1,
            ];
            $follower_count = D('attention')->attentioncount($follower_where);          //粉丝已查看数量
            $follower_new = [
                "userided" => $data['user_id'],
               // "status" => 0,
            ];
            $follower_new = D('attention')->attentioncount($follower_new);          //粉丝未查看数量
            $history_count = VisitorMemberService::getInstance()->oldcount($data['user_id']);          //最近访客查看数量
            $history_new = VisitorMemberService::getInstance()->Newcount($data['user_id']);          //最近访客未查看数量
            $number_info['attention_count'] = $attention_count;
            $number_info['follower_count'] = $follower_count;
            $number_info['follower_new'] = $follower_new;
            $number_info['history_count'] = $history_count;
            $number_info['history_new'] = $history_new;
            //最近访客数据操作(当前用户只能进行访问一次,只能更新最新时间数据)
            //访客信息记录加入功能(当前id与查看用户id)
            if($uid !== $data['user_id']){
                $result = VisitorMemberService::getInstance()->getFind($uid,$data['user_id']);
//                var_dump($result);die();
                if(empty($result)){
                    //获取数据
                    $history['uid'] = $uid;
                    $history['touid'] = $data['user_id'];
                    $history['ctime'] = time();
                    $history['device'] = $data['device'];
                    $history['access_ip'] = $_SERVER['REMOTE_ADDR'];
                    $history['status'] = 1;
                    //加入数据库
                    D('visitor_member')->add($history);
                }else{
                    //修改当前用户访客时间
                    VisitorMemberService::getInstance()->updateTime($uid,$data['user_id']);
                }
            }
            //排行榜接口列表(给当前用户当月赠送礼物最多的三个用户头像)
            $month_begindate=date('Y-m-01 00:00:00');       //当月起始时间
            $month_enddate=date('Y-m-d H:i:s');                              //当前时间
            //统计当前当前起始时间与当前时间的赠送最多的三个用户数据
            $monthcondition = array(
                "action" => "get_gift",
                "addtime >= '".$month_begindate."' and addtime <= '".$month_enddate."'",
                "uid"=>$data['user_id'],
            );
            $rank_list=D('beandetail')->randmember($monthcondition);
            if($rank_list){
                foreach($rank_list as $keys=>&$values){
                    $rank_listes[$keys]['user_id'] = $values['get_uid'];
                    $rank_listes[$keys]['avatar'] = C("APP_URL").MemberService::getInstance()->getOneByIdField($values['get_uid'],"avatar");
                }
            }else{
                $rank_listes = [];
            }
            $result = [
                "user_info" => $user_info,      //用户信息
                "grade_info" => $grage_info,       //等级信息
                "number_info" => $number_info,      //关注,粉丝,最近访客
                "member_avatar" => $member_avatar,      //用户头像列表
                "room_info" => $room_info,             //房间信息
                "rank_list" => $rank_listes,             //排行榜信息列表
            ];

            $this -> returnCode = 200;
            $this -> returnData=$result;

        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

    }
    //用户等级方法
    private function gradefun($gf,$arr)//用户等级函数
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
     * 用户信息修改接口:请求方式为get http://localhost/yidai/yidai/index.php/Api/Member/edit?token=4c1ecd48e1662d2dd0366eaa0c37ef2c&profile={%22nickname%22:%22123%22}
     * @param $token    用户token值
     * @param $profile  所要修改的用户{"username":"ceshi"}
     * 返回值说明:在修改用户的基本信息时，得更新redis里面对应的缓存信息的修改
     */
    public function edit($token,$profile,$signature=null){
        //获取数据
         $data = [
             "token" => I('post.token'),
         ];
       /* $token = I('post.token');
        $profile = I('post.profile');
        $signature = I('post.signature');*/
        try{
            if($data['token']){
                $user_info['id'] = RedisCache::getInstance()->get($data['token']);
            }else{
                E("用户TOKEN参数不正确",2000);
            }
           /* if($signature!== md5(strtolower($token))){
                E("验签失败",2000);
            }*/
            $profile = stripslashes($profile);  //过滤数据
            $profile = json_decode($profile, true); //将josn转化为数组
            $profile = $this->sanitationProfile($profile);  //设置对应的字段
            $result_keys = array_keys($profile);    //获取修改的键
            $result_values = array_values($profile);    //获取修改的值
            //检验数据
            if($result_keys[0] == 'nickname'){
                //重复的数据不能修改
                $is_repeat['nickname'] = MemberService::getInstance()->getOneByIdField($user_info['id'],"nickname");
                if($is_repeat['nickname'] == $result_values[0]){
                    E("该当前用户已重复",2000);
                }
            }
            //数据操作
            $result = D('member')->updateById($user_info['id'],$result_keys[0],$result_values[0]);
            /*if($result){
                //获取对应数据的用户名
                $data['username'] = MemberService::getInstance()->getOneByIdField($user_info['id'],"username");
                //修改redis里面对应的用户字段
                RedisCache::getInstance()->hSet($data['username'].'-'.$user_info['id'],$result_keys[0],$result_values[0]);
                $this -> returnCode = 200;
            }else{
                E("用户修改失败",4000);
            }*/

//            $this -> returnCode = 200;
            if($result_keys[0] == "birthday"){
                $user_info['birthday'] = MemberService::getInstance()->getOneByIdField($user_info['id'],"birthday");
                $user_info['twelve_animals'] = birthext($user_info['birthday']);
                $result = [
                    "user_info" => $user_info,      //用户信息
                ];
                $this -> returnData=$result;
            }
            $this -> returnCode = 200;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();
    }

    /**
     * 净化用户输入的个人资料.
     * @param array $profile.
     * @return array 净化之后的用户信息.
     */
    private function sanitationProfile($profile)
    {
        $filter_map = array(
            'nickname' => 'htmlspecialchars',
            'intro' => 'htmlspecialchars',
            'birthday' => 'htmlspecialchars',
            'city' => 'htmlspecialchars',
           /* 'latitude' => 'floatval',
            'longitude' => 'floatval',*/
        );
        foreach ($filter_map as $field => $func) {
            if (!isset($profile[$field])) {
                continue;
            }
            $profile[$field] = $func($profile[$field]);
        }

        return $profile;
    }

    /**举报用户接口功能
     * @param $token    token值
     * @param $to_uid   举报对象用户id
     * @param $contents 举报内容,是金条
     * @param $signature    签名md5(strtolower(token+to_uid))
     * 接口说明:一天只能举报一次该用户
     */
    public function complain($token,$to_uid,$contents,$signature){
        $data = [
            "token" => I('post.token'),
            "to_uid" => I('post.to_uid'),
            "contents" => I("post.contents"),
            "signature" => I('post.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("to_uid",$data['to_uid'],1);
            $user_info['uid'] = RedisCache::getInstance()->get($data['token']);     //用户id
            /*if($signature!== md5(strtolower($data['token'].$data['to_uid']))){
                E("验签失败",2000);
            }*/
            //一天只能举报一次该用户
            $dataes = [
                "user_id" => $user_info['uid'],
                "to_uid" => $data['to_uid'],
            ];

            $res['create_time'] = ComplaintsServer::getInstance()->getFieldTime($dataes);
            //将时间戳转化为时间格式,只取得对应的Y-m-d
            $create_time = date("Y-m-d",$res['create_time']);
            $datatime = date("Y-m-d",time());
            if($datatime !== $create_time){
                $addData = [
                    "user_id" => $user_info['uid'],
                    "to_uid" => $data['to_uid'],
                    "contents" => $data['contents'],
                    "create_time" => time(),
                ];
                D("complaints")->add($addData);
            }else{
                E("您已举报过此用户",2000);
            }
            $this -> returnCode = 200;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();
    }

    /**拉黑接口
     * @param $token    token值
     * @param $user_id  用户id
     * @param $signature    签名(md5(strtolower(token+user_id))
     */
    public function defriend($token,$user_id,$signature=null){
        //获取数据
        $data = [
            "token" => I('post.token'),
            "user_id" => I('post.user_id'),
            "signature" => I('post.signature'),
        ];
        try{
            $user_info['uid'] = RedisCache::getInstance()->get($data['token']);
            //验签数据
            if($data['signature']!== md5(strtolower($data['token'].$data['user_id']))){
                E("验签失败",2000);
            }
            //环信操作拉黑
            $Easemob=new Easemob();
            //检测当前用户是否已拉黑操作
            $Blacklist = $Easemob->getBlacklist($user_info['uid']);
            if(in_array($data['user_id'],$Blacklist["data"])){
                E("该用户已被拉黑",2000);
            }
            //拉黑用户操作
            $usernames=array(
                "usernames"=>array($data['user_id'])        //这里是一个二维数组
            );
            $Easemob->addUserForBlacklist($user_info['uid'],$usernames);
            $this -> returnCode = 200;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();
    }

    /**取消拉黑接口
     * @param $token    token值
     * @param $user_id  用户id
     * @param null $signature   签名(md5(strtolower(token+user_id))
     */
    public function blackout($token,$user_id,$signature=null){
        //获取数据
        $data = [
            "token" => I('post.token'),
            "user_id" => I('post.user_id'),
            "signature" => I('post.signature'),
        ];
        try{
            $user_info['uid'] = RedisCache::getInstance()->get($data['token']);
            //验签数据
            /*if($data['signature']!== md5(strtolower($data['token'].$data['user_id']))){
                E("验签失败",2000);
            }*/
            //环信操作拉黑
            $Easemob=new Easemob();
            //检测当前用户是否在拉黑列表里操作
            $Blacklist = $Easemob->getBlacklist($user_info['uid']);
            if(!in_array($data['user_id'],$Blacklist["data"])){
                E("该用户不存在",2000);
            }
            //取消拉黑用户操作
            $Easemob->deleteUserFromBlacklist($user_info['uid'],$data['user_id']);
            $this -> returnCode = 200;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();
    }

	/**用户头像上传接口
     * @param $token token值
     * @param $avatar   avatar上传图片的详情
     * @param $avatarid  修改头像(多张)
     */
    public function avatar($token,$avatarid=null){
//        include_once "./ThinkPHP/Library/Vendor/OSS/autoload.php";
//        include_once "./ThinkPHP/Library/Vendor/OSS/src/OSS/OssClient.php";
        $data['token'] = I('post.token');
        $user_info['id'] = RedisCache::getInstance()->get($data['token']);
//        var_dump($user_info['id']);die();
//        $user_info['id'] = '1000064';
//        var_dump($user_info['id']);die();
        //获取图片
        if($_FILES["avatar"]["error"] != 0){
            $this -> error("上传图片有误");
            die();
        }
        // 处理图片
        $upload = new \Think\Upload();// 实例化上传类
        $upload -> maxSize = 1024*1024*10 ;// 设置附件上传大小
        $upload -> exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './Public';
        $upload->savePath = '/Uploads/user/'; // 设置附件上传目录
        vendor('OSS.autoload');
        $ossConfig= C('OSS');
        $accessKeyId= $ossConfig['ACCESS_KEY_ID'];//阿里云OSS  ID
        $accessKeySecret= $ossConfig['ACCESS_KEY_SECRET'];//阿里云OSS 秘钥
        $endpoint= $ossConfig['ENDPOINT'];//阿里云OSS 地址
        $ossClient= new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间

        // 上传文件
        $info = $upload -> upload();
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
            die();
        }else{// 上传成功
            $dir = "dir1";
            foreach ($info as $k => $v){
//                $object  = $dir . '/' . $v['name'];//想要保存文件的名称
                $object  = $dir . '/' .$user_info['id'].'_'.$v['savename'];//想要保存文件的名称
                //这个数组是存上传成功以后返回的访问路径，多文件时使用implode函数将其组合

                $downlink[] = $bucket.'.'.$endpoint.'/'.$object;
                $file= './Public' . $info[$k]['savepath'] . $v['savename'];//本地文件路径
//                $_POST["avatar"] = '/Public'.$info["avatar"]["savepath"].$info["avatar"]["savename"];

                try {
//                    $resultimage = $ossClient->uploadFile($bucket, $object, $file);
                    $resultimage = $ossClient->uploadFile($bucket, $object, $file);
                    //上传成功
                    //这里可以删除上传到本地的文件。
                    unlink($file);
                } catch (OssException $e) {
                    //上传失败
                    printf($e->getMessage() . "\n");
                    return;
                }
            }

        }
        try{
            $user_info['id'] = RedisCache::getInstance()->get($data['token']);
            //数据操作
            $result = D('member')->updateById($user_info['id'],"avatar","/".$object);
//            var_dump($result);die();
            $this -> returnCode = 200;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();
    }


    /**用户头像上传接口
     * @param $token token值
     * @param $avatar   avatar上传图片的详情
     * @param $avatarid  修改头像(多张)
     */
    public function avatarceshi($token,$avatarid=null){
        $data['token'] = I('post.token');
        //获取图片
        if($_FILES["avatar"]["error"] != 0){
            $this -> error("上传图片有误");
            die();
        }
        // 处理图片
        $upload = new \Think\Upload();// 实例化上传类
        $upload -> maxSize = 1024*1024*10 ;// 设置附件上传大小
        $upload -> exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './Public';
        $upload->savePath = '/Uploads/user/'; // 设置附件上传目录
        // 上传文件
        $info = $upload -> upload();
        if(!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
            die();
        }else{// 上传成功
            $_POST["avatar"] = '/Public'.$info["avatar"]["savepath"].$info["avatar"]["savename"];
        }
        try{
            $user_info['id'] = RedisCache::getInstance()->get($data['token']);
            //删除原用户头像
            /*$row = MemberService::getInstance()->getOneByIdField($user_info['id'],"avatar");
            unset($row);*/
//            $picPath = basename($row);
          /*  var_dump($picPath);die();
            $picPath = parse_url($row['avatar']);
//            $data = unlink($picPath['path']);
            var_dump($picPath['path']);die();*/
           /* if(!unlink($picPath['path'])){
                E("修改图像失败",2000);
            }*/
            //数据操作
            $result = D('member')->updateById($user_info['id'],"avatar",$_POST["avatar"]);
            if($result){
               /* $result = D('member')->updateById($user_info['id'],"room_image",$_POST["avatar"]);
                $result_keys[0] = "room_image";
                $result_values[0] = $_POST["avatar"];
                LanguageroomService::getInstance() -> getUpdate($data['room_id'],$result_keys[0],$result_values[0]);*/
                $this -> returnCode = 200;
            }else{
                E("用户头像修改失败",2000);
            }
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();
    }

    /**粉丝贡献列表数据
     * @param $token    token值
     * @param $status   1日2周3月
     *  @param null $signature   签名(md5(strtolower(token))
     */
    public function rank_list($token,$status,$signature=null){
        //获取数据值
        $data = [
            "token" => I('post.token'),
            "status" => I('post.status'),
            "signature" => I('post.signature'),
        ];
        try{
            $user_info['user_id'] = RedisCache::getInstance()->get($data['token']);
//            $user_info['user_id'] = '1000021';
            //验签数据
            /*if($data['signature']!== md5(strtolower($data['token'].$data['user_id']))){
                E("验签失败",2000);
            }*/
            if($data['status'] == 1){   //今天数据
                $day_begindate=date('Y-m-d 00:00:00');       //今天起始时间
                $day_enddate=date('Y-m-d H:i:s');                              //今天结束时间
                //统计当前当前起始时间与当前时间的赠送最多的三个用户数据
                $daycondition = array(
                    "addtime >= '".$day_begindate."' and addtime <= '".$day_enddate."'",
                    "action" => "get_gift",
                    "uid"=>$user_info['user_id'],
                );
                $rank_list=D('beandetail')->rand_member($daycondition);
            }else if($data['status'] == 2){ //最近一周数据(最近一周数据)
                //最近一周
                $now = time();
                $resultweek = [];
                for($i=0;$i<7;$i++){
                    $resultweek[] = date('Y-m-d',strtotime('-'.$i.' day', $now));
                }
                $week_begindate=array_pop($resultweek)." "."00:00:00";       //最近一周数据
                $week_enddate=date('Y-m-d H:i:s',time());                             //最近一周数据
                //统计当前当前起始时间与当前时间的赠送最多的三个用户数据
                $weekcondition = array(
                    "addtime >= '".$week_begindate."' and addtime <= '".$week_enddate."'",
                    "action" => "get_gift",
                    "uid"=>$user_info['user_id'],
                );
                $rank_list=D('beandetail')->rand_member($weekcondition);
            }else if($data['status'] == 3){     //前一月数据(当前时间前一号到现在的时间)
                $month_begindate=date('Y-m-01 00:00:00');       //当月起始时间
                $month_enddate=date('Y-m-d H:i:s');                              //当前时间
                //统计当前当前起始时间与当前时间的赠送最多的三个用户数据
                $monthcondition = array(
                    "addtime >= '".$month_begindate."' and addtime <= '".$month_enddate."'",
                    "action" => "get_gift",
                    "uid"=>$user_info['user_id'],
                );
                $rank_list=D('beandetail')->rand_member($monthcondition);
            }
            if($rank_list){
                foreach($rank_list as $keys=>$values){
                    $rank_listes[$keys]['user_id'] = $values['get_uid'];
                    $rank_listes[$keys]['avatar'] = C("APP_URL").MemberService::getInstance()->getOneByIdField($values['get_uid'],"avatar");
                    $rank_listes[$keys]['nickname'] = MemberService::getInstance()->getOneByIdField($values['get_uid'],"nickname");
                    $rank_listes[$keys]['sex'] = MemberService::getInstance()->getOneByIdField($values['get_uid'],"sex");
                    //根据当前用户uid,去查询对应访问user_id的消费总和,(uid向此用户user_id消费总和)
                    $duke_coin = CoindetailService::getInstance()->getMembercoin($user_info['user_id'],$values['get_uid']); //用户统计数据操作
                    $rank_listes[$keys]['dukename'] = duke_grade($duke_coin[0]['coin']);  //爵位等级
                    $rank_listes[$keys]['user_lv'] = lv_dengji(floor($values['coin']));  //等级;
                    $total_expvalue = D("member")->merge_exp($values['get_uid']);
                    $rank_listes[$keys]['vip_lv'] = vip_grade($total_expvalue['total_expvalue']);  //vip等级
                    $rank_listes[$keys]['coin'] = $values['coin'];
                }
            }else{
                $rank_listes = [];
            }
            $this -> returnCode = 200;
            $this -> returnMsg = "操作成功";
            $this -> returnData=$rank_listes;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();

        }
        $this -> returnData();
    }
}


?>