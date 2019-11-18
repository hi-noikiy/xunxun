<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Api\Service\DebarService;
use Api\Service\AttentionService;
use Api\Service\VisitorMemberService;
use Api\Service\MemberAvatarService;
use Api\Service\MonitoringService;
use Api\Service\RoomMemberService;
use Api\Service\LanguageroomService;
use Api\Service\ComplaintsServer;
use Api\Service\CoindetailService;
use Api\Service\PushMsgService;
use Api\Service\GiftService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\ValiData;

use Common\Util\emchat\Easemob;
use Think\Exception;
use Think\Log;

class MemberController extends BaseController
{

    //验证码 redis key
    private $user_code_key = 'verify_code_';
    private $token_expires_time = 864000;

    //根据id获取用户基础信息
    public function getuserinfos()
    {
        $user_info = [];
        $uids = $_REQUEST['userids'];
        $uids = trim($uids);
        $uids = rtrim($uids, ',');
        if (empty($uids)) {
            $this->returnCode = 200;
            $this->returnMsg = "参数不能为空";
            $this->returnData = [];
        }

        $uidArr = explode(',', $uids);
        $uidArr = array_slice($uidArr, 0, 50);
        if (empty($uidArr)) {
            $this->returnCode = 200;
            $this->returnMsg = "参数错误";
            $this->returnData = [];
        }
        $where['id'] = array('in', implode(',', $uidArr));
        $user_info = D('member')->where($where)->field('id,pretty_id,nickname,sex,intro,pretty_avatar,pretty_avatar_svga,avatar,birthday,city,lv_dengji')->select();
        foreach ($user_info as $key => $value) {
            $user_info[$key]['avatar'] = $value['avatar'] ? C('APP_URL_image') . $value['avatar'] : '';
            $user_info[$key]['pretty_avatar'] = $value['pretty_avatar'] ? C('IMG_URL') . $value['pretty_avatar'] : '';
            $user_info[$key]['pretty_avatar_svga'] = $value['pretty_avatar_svga'] ? C('IMG_URL') . $value['pretty_avatar_svga'] : '';
            $user_info[$key]['intro'] = $value['intro'] ? $value['intro'] : '';
            $user_info[$key]['birthday'] = $value['birthday'] ? $value['birthday'] : '';
            $user_info[$key]['city'] = $value['city'] ? $value['city'] : '';
            $user_info[$key]['lv_dengji'] = $value['lv_dengji'] ? $value['lv_dengji'] : '';
        }
        $this->returnCode = 200;
        $this->returnMsg = "操作成功";
        $this->returnData = $user_info;

    }


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
    protected $supported_type = array('qq', 'wechat', 'sina', 'facebook', 'twitter', 'instagram');

    public function login($username, $vertify = null)
    {
        $username = $_REQUEST['username'];
        $vertify = $_REQUEST['vertify'];
        try {
            if (empty($username) || empty($vertify)) {
                E("手机或验证码错误", 2002);
            }
            //校验验证码是否正确
            if (C('ISLOGIN') == 1) {
                $code = RedisCache::getInstance()->get($this->user_code_key . $username);
                if ($vertify !== $code) {
                    E("验证码错误", 2002);
                }
            }

            //判断是否第一次登陆 
            $field = 'id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber,login_time,attestation';
            $user_info = D('member')->getByqopenid(['username' => $username], $field);
            if (empty($user_info)) {
                E('第一次注册', 2000);
            } else {
                $user_info[0]['avatar'] = $user_info[0]['avatar'] ? C('APP_URL_image') . $user_info[0]['avatar'] : '';
                $userInfo = $user_info[0];
                // 修改登录时间及ip地址 (存缓存)
                // $res = MemberService::getInstance() ->updateLoginTimeIp($user_info[$id]['userid']);
                $loginKey = 'login_time_ip';
                $time = date('Y-m-d H:i:s', time());
                $redisValue = $time . '_' . gets_client_ip();
                RedisCache::getInstance()->HSET($loginKey, $userInfo['userid'], $redisValue);
                //生成token,并且将token存储在redis中
                // 如果用户之前自动登录过,还有token,删除原来的token.
                $token = RedisCache::getInstance()->get($userInfo['userid']);
                if ($token) {
                    RedisCache::getInstance()->delete($userInfo['userid']);
                    RedisCache::getInstance()->delete($token);
                }
                $token = generateToken(C('SALT'));
//                $res = RedisCache::getInstance()->getRedis()->set($userInfo['userid'], $token);
                $res = RedisCache::getInstance()->getRedis()->SETEX($userInfo['userid'],$this->token_expires_time, $token);
                // $token = RedisCache::getInstance()->get($userInfo['userid']);
                // 存入Token数据,API部分后续使用.todo对于set需要重构(对于数组是不能存储的)
                RedisCache::getInstance()->getRedis()->SETEX($token, $this->token_expires_time,$userInfo['userid']);

                $data = $user_info[0];
                // $data['userid'] = $data['id'];
                $data['username'] = '';
                $data['token'] = $token;
                $data['firstlogin'] = "2";
                // $user_info[$id]['token'] = $token;
                // $user_info[$id]['firstlogin'] = "2";
                // $user_info=dealnull($user_info[$id]);  
                // $data=array('login_status'=>$token);
                // $res = D('member')->updateDate($userInfo['id'],array('login_status'=>$token));
                //缓存数据
                //缓存数据
                $userKey = "userinfo_" . $userInfo['userid'];
                $userKeys = RedisCache::getInstance()->getRedis()->EXISTS($userKey);
                if (empty($userKeys)) {
                    $user_redis = M("member")->where(array("id" => $userInfo['userid']))->find();
                    RedisCache::getInstance()->hmset($userKey, $user_redis);
                }
                if ($res) {
                    //一切成功后想验证码redis销毁
                    RedisCache::getInstance()->delete($this->user_code_key . $username);

                    //判断缓存是否存在MQTTtoken
                    $conf = C('MQTT');
                    $MqttKey = 'MQTT_P2P_'.$userInfo['userid'];
                    $MqttToken = RedisCache::getInstance()->getRedis()->get($MqttKey);
                    $MqttTokenTtl = RedisCache::getInstance()->getRedis()->ttl($MqttKey);
                    if ($MqttToken && $MqttTokenTtl > 172800) {
                        // RedisCache::getInstance()->getRedis()->setex($MqttKey,$expireTime,$resmq['tokenData']);
                        $data['mqtoken'] = $MqttToken;
                        $data['mqusername'] = 'Token|' . $conf['accessKey'] . '|' . $conf['instanceId'];
                        $data['mqpassword'] = 'R|' . $MqttToken;
                        $data['mqttl'] = $MqttTokenTtl;
                    }else{
                        list($resmq,$expireTime) = $this->sendMqtt($MqttKey,$userInfo['userid']);
                        $resmq = json_decode($resmq,true);
                        if (isset($resmq['code']) && $resmq['code'] == 200) {
                            RedisCache::getInstance()->getRedis()->setex($MqttKey,$expireTime,$resmq['tokenData']);
                            $data['mqtoken'] = $resmq['tokenData'];
                            $data['mqusername'] = 'Token|' . $conf['accessKey'] . '|' . $conf['instanceId'];
                            $data['mqpassword'] = 'R|' . $resmq['tokenData'];
                            $data['mqttl'] = $expireTime;
                        }else{
                            $data['mqtoken'] = '';
                            $data['mqusername'] = '';
                            $data['mqpassword'] = '';
                            $data['mqttl'] = 0;
                        }
                    }
                    
                    
                    
                    $result = [
                        "info" => $data,
                        "username" => '',
                    ];
                    $this->returnCode = 200;
                    $this->returnMsg = "操作成功";
                    $this->returnData = $result;
                } else {
                    $this->returnCode = 201;
                    $this->returnMsg = "操作失败";
                }
            }
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        $this->returnData();
    }

    //MQTT获取token
    protected function sendMqtt($MqttKey,$uid)
    {
        // //判断缓存是否存在MQTTtoken
        // // $MqttKey = 'MQTT_ROOM_'.$userid;
        // $MqttToken = RedisCache::getInstance()->getRedis()->get($MqttKey);
        // $MqttTokenTtl = RedisCache::getInstance()->getRedis()->ttl($MqttKey);
        // if ($MqttToken && $MqttTokenTtl > 10800) {
        //     return [$MqttToken,$MqttTokenTtl];
        // }


        $conf = C('MQTT');
        $tokenUrl = $conf['tokenurl'].'/token/apply';
        $action = 'R';
        $resources = $conf['topic'].'/p2p/#'.','.$conf['topic'].'/self/#'; //多个需要字典排序
        $expireTime = msectime() + 1728000000; //过期时间加20天毫秒
        // $expireTime = substr_replace($expireTime, $uid, -7,7); //替换时间用户id结尾
        $instanceId = $conf['instanceId'];
        $str = sprintf('actions=%s&expireTime=%d&instanceId=%s&resources=%s&serviceName=mq',$action,$expireTime,$instanceId,$resources);
        $signature = base64_encode(hash_hmac("sha1", $str, $conf['secretKey'],true));
        
        $params['accessKey'] = $conf['accessKey'];
        $params['actions'] = $action;
        $params['expireTime'] = $expireTime;
        $params['instanceId'] = $instanceId;
        $params['proxyType'] = 'MQTT';
        $params['resources'] = $resources;
        $params['serviceName'] = 'mq';
        $params['signature'] = $signature;
        $res = curlData($tokenUrl,$params,'POST','form-data');
        return [$res,1728000];
    }


    public function login_bak($username, $vertify = null)
    {
        $username = $_REQUEST['username'];
        $vertify = $_REQUEST['vertify'];
        // $id = D('member')->getByuid($username);
        try {
            //登录部分
            //最终返回数据
            $field = 'id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber,login_time';
            $id = D('member')->getByuid($username);
            //查询该用户数据
            $user_info = D('member')->getOneByIdField($id, $field);
            $user_info[$id]['username'] = "";
            $where = ['username' => $username];
            $phone = D('member')->idFind($where);
            ParamCheck::checkMobile("username", $username);
            if (C('ISLOGIN') == 1) {
                if (!empty($vertify)) {
                    //校验验证码是否正确
                    $code = RedisCache::getInstance()->get('verify_code_' . $username);
                    if ($vertify !== $code) {
                        E("验证码错误", 2002);
                    }
                } else {
                    E("验证码不能为空", 2003);
                }
            }

            //数据操作(根据用户名查询用户在不在数据中)


            if (empty($id) || empty($phone)) {
                E('第一次注册', 2000);
            } else {
                //验证码登录用户存在的情况下
                // 修改登录时间及ip地址
                $res = MemberService::getInstance()->updateLoginTimeIp($user_info[$id]['userid']);
                //生成token,并且将token存储在redis中
                // 如果用户之前自动登录过,还有token,删除原来的token.
                $token = RedisCache::getInstance()->get($user_info[$id]['userid']);
                if ($token) {
                    RedisCache::getInstance()->delete($user_info[$id]['userid']);
                    RedisCache::getInstance()->delete($token);
                }
                $token = generateToken(C('SALT'));
                $res = RedisCache::getInstance()->set($user_info[$id]['userid'], $token);
                $token = RedisCache::getInstance()->get($user_info[$id]['userid']);
//                    var_dump($token);
                // 存入Token数据,API部分后续使用.todo对于set需要重构(对于数组是不能存储的)
                RedisCache::getInstance()->set($token, $user_info[$id]['userid']);
                $user_info[$id]['token'] = $token;
                $user_info[$id]['firstlogin'] = "2";
                $user_info = dealnull($user_info[$id]);
                $data = array('login_status' => $token);
                $res = D('member')->updateDate($user_info['userid'], $data);
                if ($res) {
                    $result = [
                        "info" => $user_info,
                        "username" => $username,
                    ];
                    $this->returnCode = 200;
                    $this->returnMsg = "操作成功";
                    $this->returnData = $result;
                } else {
                    $this->returnCode = 201;
                    $this->returnMsg = "操作失败";
                }
            }
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }

        $this->returnData();
    }


    /**
     * 退出登录,Token设置为过期.
     *
     * @param token
     */
    public function logout($token = null)
    {
        try {
            $user_id = RedisCache::getInstance()->get($token);
            RedisCache::getInstance()->delete($user_id);
            RedisCache::getInstance()->delete($token);
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        $this->returnData();
    }

    /**
     * 第三方登录接口.
     *
     * @param $openid  The openid.
     * @param $type    平台类型,比如QQ/Sina/Wechat.
     * @param $payload 平台回调数据.
     */
    //
    public function thirdlogin($openid, $type, $payload, $param = null)
    {
        //获取数据
        $openid = $_REQUEST['openid'];
        $type = $_REQUEST['type'];
        $payload = $_REQUEST['payload'];
        // var_dump($payload);die;
        // var_dump($data);die;
        try {
            $type = strtolower($type);
            if (!in_array($type, $this->supported_type)) {
                E('暂不支持的类型', 2000);
            }

            if (in_array($type, array("wechat", "facebook"))) {
                //var_dump(123);die;
                $payload = json_decode($payload, true);
                // var_dump($payload);die;
            } else {
                $payload = json_decode(stripcslashes($payload), true);
            }

            // var_dump($payload);die;
            if (empty($payload)) {
                E('数据有误,不是正确的json格式！', 2001);
            }

            //验证账号状态是否可以登录后期加现在没有规则
            // $ua->accountStatus($type,$openid);
            switch ($type) {
                case 'qq':
                    $user = $this->QQ($openid, $payload);
                    $this->returnCode = 200;
                    $this->returnData = $user;
                    break;
                case 'sina':
                    $user = $this->Sina($openid, $payload);
                    $this->returnCode = 200;
                    $this->returnData = $user;
                    break;
                case 'wechat':
                    //返回用户信息
                    $user = $this->Wechat($openid, $payload);
                    $this->returnCode = 200;
                    $this->returnData = $user;
                    break;
                case 'facebook':
                    $user = $this->Facebook($openid, $payload);
                    break;
                case 'twitter':
                    $user = $this->Twitter($openid, $payload);
                    break;
                case 'instagram':
                    $user = $this->Instagram($openid, $payload);
                    break;

                default:
                    E('暂不支持的类型', 2002);
            }


        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        $this->returnData();
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
        $field = 'id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';
        $user_info_where = array(
            'qopenid' => $openid,
        );
        $username = D('member')->getByqopenid($user_info_where, "username");
        if (!empty($username)) {
            $id = D('member')->getByqopenid($user_info_where, 'id');
            $user_info = D('member')->getByqopenid($user_info_where, $field);
            $token = RedisCache::getInstance()->get($user_info[$id]['userid']);
            //var_dump($token);die;
            if ($token) {
                RedisCache::getInstance()->delete($user_info[$id]['userid']);
                RedisCache::getInstance()->delete($token);
            }

            $token = generateToken(C('SALT'));
            RedisCache::getInstance()->set($user_info[$id]['userid'], $token);
            // 存入Token数据,API部分后续使用.todo对于set需要重构(对于数组是不能存储的)
            RedisCache::getInstance()->set($token, $user_info[$id]['userid']);
            $user_info[$id]['token'] = $token;
            $user_info = dealnull($user_info[$id]);
            $result = [
                'openid' => $openid,
                'status' => "1",
                "info" => $user_info,
                'payload' => $data,
            ];
            return $result;
        } else {
            $result = [
                'openid' => $openid,
                'status' => "0",
                "info" => [],
                'payload' => $data,
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
        $field = 'id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';
        $user_info_where = array(
            0 => 'wxunionid = "' . $data['unionid'] . '"',
        );
        // var_dump($data['unionid']);die;
        $username = D('member')->getByqopenid($user_info_where, "username");
        //var_dump($user_info);die;
        if (!empty($username)) {
            $id = D('member')->getByqopenid($user_info_where, "id");
            $user_info = D('member')->getByqopenid($user_info_where, $field);
            $token = RedisCache::getInstance()->get($user_info[$id]['userid']);
            //var_dump($token);die;
            if ($token) {
                RedisCache::getInstance()->delete($user_info[$id]['userid']);
                RedisCache::getInstance()->delete($token);
            }

            $token = generateToken(C('SALT'));
            RedisCache::getInstance()->set($user_info[$id]['userid'], $token);
            // 存入Token数据,API部分后续使用.todo对于set需要重构(对于数组是不能存储的)
            RedisCache::getInstance()->set($token, $user_info[$id]['userid']);
            $user_info[$id]['token'] = $token;
            $user_info = dealnull($user_info[$id]);
            $result = [
                'openid' => $openid,
                'status' => "1",//已经绑定过 直接跳转首页
                "info" => $user_info,
                'payload' => $data,

            ];
            return $result;
        } else {
            $result = [
                'openid' => $openid,
                'status' => "0",//没有绑定过，进入绑定页面
                "info" => [],
                'payload' => $data,

            ];
            return $result;
        }
    }


    public function updatesex_bak($username, $sex)
    {
        try {
            $phone = D('member')->getOneByUserField($username, 'username');
            if (!empty($phone)) {
                //log:record('aaaaaaaaa'.json_encode($phone),'INFO');               
                $this->returnCode = 203;
                $this->returnMsg = "此手机号已被注册";
                $this->returnData();
            }
            $field = 'id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';
            //实例化数据
            $time = date('Y-m-d H:i:s', time());
            $gets_client_ip = gets_client_ip();
            $value = [
                "username" => $username,
                "register_time" => $time,
                "register_ip" => $gets_client_ip,
                "login_time" => $time,
                "login_ip" => $gets_client_ip,
                'sex' => $sex,
            ];
            $data = D('member')->addData($value);
            //判断靓号
            $memberPretty = D("member_pretty")->getList();
            if (!empty($memberPretty) && in_array($data, $memberPretty)) {
                $setPretty = D('member')->updateDate($data, ['username' => $username . '00']);
                $data = D('member')->addData($value);
                if (!$setPretty || !$data) {
                    $this->returnCode = 500;
                    $this->returnMsg = "账号冲突注册失败";
                }
            }

            if ($data) {
                $ids = D('member')->getByuid($username);
                $Easemob = new Easemob();     //环信第三方注册
                $eas = $Easemob->createUser($ids, $ids, $ids);
                Log::record("huanxin--------" . json_encode($eas), "INFO");
                if (isset($eas['error'])) {
                    $this->returnCode = 201;
                    $this->returnMsg = "语音写入用户失败";
                    $this->returnDate = $eas;
                } else {
                    $user_info = D('member')->getOneByIdField($ids, $field);
                    // 修改登录时间及ip地址
                    // MemberService::getInstance() ->updateLoginTimeIp($user_info[$ids]['userid']);
                    $loginKey = 'login_time_ip';
                    $time = date('Y-m-d H:i:s', time());
                    $redisValue = $time . '_' . gets_client_ip();
                    RedisCache::getInstance()->HSET($loginKey, $ids, $redisValue);
                    //生成token,并且将token存储在redis中
                    $token = RedisCache::getInstance()->get($ids);
                    if ($token) {
                        RedisCache::getInstance()->delete($ids);
                        RedisCache::getInstance()->delete($token);
                    }
                    $token = generateToken(C('SALT'));
                    RedisCache::getInstance()->set($ids, $token);
                    RedisCache::getInstance()->set($token, $ids);
                    $user_info[$ids]['token'] = $token;
                    $user_info = dealnull($user_info[$ids]);
                    $param = array('login_status' => $token, 'nickname' => "用户_" . $data, 'pretty_id' => $data);
                    D('member')->updateDate($ids, $param);
                    $result = [
                        "info" => $user_info,
                    ];
                    $this->returnCode = 200;
                    $this->returnMsg = "操作成功";
                    $this->returnData = $result;
                }
            } else {
                $this->returnCode = 202;
                $this->returnMsg = "用户写入失败";
            }
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        $this->returnData();
    }

    //更新性别接口
    //$type 1 普通注册 2QQ3Wechat
    public function updatesex($username, $sex)
    {
        try {
            $phone = D('member')->getOneByUserField($username, 'username');
            if (!empty($phone)) {
                //log:record('aaaaaaaaa'.json_encode($phone),'INFO');
                $this->returnCode = 203;
                $this->returnMsg = "此手机号已被注册";
                $this->returnData();
            }
            $field = 'id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber,attestation';
            //实例化数据
            $time = date('Y-m-d H:i:s', time());
            $gets_client_ip = gets_client_ip();
            $value = [
                "username" => $username,
                "register_time" => $time,
                "register_ip" => $gets_client_ip,
                "login_time" => $time,
                "login_ip" => $gets_client_ip,
                'sex' => $sex,
            ];
            $data = D('member')->addData($value);
            //判断靓号
            $memberPretty = D("member_pretty")->getList();
            if (!empty($memberPretty) && in_array($data, $memberPretty)) {
                $setPretty = D('member')->updateDate($data, ['username' => $username . '00']);
                $data = D('member')->addData($value);
                if ($setPretty && $data) {
                    $this->returnCode = 500;
                    $this->returnMsg = "账号冲突注册失败";
                }
            }

            if ($data) {
                $newparam['nickname'] = "用户_" . $data;
                $newparam['pretty_id'] = $data;
                $nike = D('member')->updateDate($data, $newparam);
                if (empty($nike)) {
                    $this->returnCode = 201;
                    $this->returnMsg = "修改昵称失败";
                } else {
                    $ids = D('member')->getByuid($username);
                    $Easemob = new Easemob();     //环信第三方注册
                    $eas = $Easemob->createUser($ids, $ids, $ids);
                    // if (isset($eas['error'])) {
                        Log::record("huanxin--------" . json_encode($eas), "INFO");
                    // }
                    // if (isset($eas['error'])) {
                    //     $this->returnCode = 201;
                    //     $this->returnMsg = "环信写入用户失败，具体错误请看Data";
                    //     $this->returnDate = $eas;
                    // } else {
                        $user_info = D('member')->getOneByIdField($ids, $field);
                        // 修改登录时间及ip地址
                        MemberService::getInstance()->updateLoginTimeIp($user_info[$ids]['userid']);
                        //生成token,并且将token存储在redis中
                        // 如果用户之前自动登录过,还有token,删除原来的token.
                        $token = RedisCache::getInstance()->get($user_info[$ids]['userid']);
                        //var_dump($token);die;
                        if ($token) {
                            RedisCache::getInstance()->delete($user_info[$ids]['userid']);
                            RedisCache::getInstance()->delete($token);
                        }
                        $token = generateToken(C('SALT'));
                        RedisCache::getInstance()->getRedis()->SETEX($user_info[$ids]['userid'],$this->token_expires_time,$token);
                        // 存入Token数据,API部分后续使用.todo对于set需要重构(对于数组是不能存储的)
                        RedisCache::getInstance()->getRedis()->SETEX($token, $this->token_expires_time,$user_info[$ids]['userid']);
                        $user_info[$ids]['token'] = $token;
                        $user_info = dealnull($user_info[$ids]);
                        $data = array('login_status' => $token);
                        D('member')->updateDate($user_info['userid'], $data);
                        $result = [
                            "info" => $user_info,
                        ];
                        $user_info = D('member')->getOneByIdField($ids, $field);
                        // 修改登录时间及ip地址
                        MemberService::getInstance()->updateLoginTimeIp($user_info[$ids]['userid']);
                        //生成token,并且将token存储在redis中
                        // 如果用户之前自动登录过,还有token,删除原来的token.
                        $token = RedisCache::getInstance()->get($user_info[$ids]['userid']);
                        if ($token) {
                            RedisCache::getInstance()->delete($user_info[$ids]['userid']);
                            RedisCache::getInstance()->delete($token);
                        }
                        $token = generateToken(C('SALT'));
                        RedisCache::getInstance()->set($user_info[$ids]['userid'], $token);
                        // 存入Token数据,API部分后续使用.todo对于set需要重构(对于数组是不能存储的)
                        RedisCache::getInstance()->set($token, $user_info[$ids]['userid']);
                        $user_info[$ids]['token'] = $token;
                        $user_info = dealnull($user_info[$ids]);
                        $data = array('login_status' => $token);
                        D('member')->updateDate($user_info['userid'], $data);
                        //缓存数据
                        $userKey = "userinfo_" . $user_info['userid'];
                        $userKeys = RedisCache::getInstance()->getRedis()->EXISTS($userKey);
                        if (empty($userKeys)) {
                            $user_redis = M("member")->where(array("id" => $user_info['userid']))->find();
                            RedisCache::getInstance()->hmset($userKey, $user_redis);
                        }
                        $user_info['username'] = '';
                        $result = [
                            "info" => $user_info,
                        ];
                        $this->returnCode = 200;
                        $this->returnMsg = "操作成功";
                        $this->returnData = $result;
                    // }
                }
            } else {
                $this->returnCode = 202;
                $this->returnMsg = "用户输入未入库";
            }
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        $this->returnData();
    }

    /*自动登录接口*/
    public function autologin($token)
    {
        try {
            $field = 'id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber,role';
            $userid = RedisCache::getInstance()->get($token);
            //新增加延长过期时间10天
            RedisCache::getInstance()->getRedis()->SETEX($userid,$this->token_expires_time, $token);
            RedisCache::getInstance()->getRedis()->SETEX($token,$this->token_expires_time,$userid);

            $user_info = D('member')->getOneByIdField($userid, $field);
            $user_info = dealnull($user_info[$userid]);
            $data = array('login_status' => $token);
            D('member')->updateDate($user_info['userid'], $data);


            $loginKey = 'login_time_ip';
            $time = date('Y-m-d H:i:s', time());
            $redisValue = $time . '_' . gets_client_ip();
            RedisCache::getInstance()->HSET($loginKey, $userid, $redisValue);
            $user_info['avatar'] = $user_info['avatar'] ? C("APP_URL") . $user_info['avatar'] : '';
            //缓存数据
            $userKey = "userinfo_" . $user_info['userid'];
            $userKeys = RedisCache::getInstance()->getRedis()->EXISTS($userKey);
            if (empty($userKeys)) {
                $user_redis = M("member")->where(array("id" => $user_info['userid']))->find();
                RedisCache::getInstance()->hmset($userKey, $user_redis);
            }
            // //判断缓存是否存在MQTTtoken
            $conf = C('MQTT');
            $MqttKey = 'MQTT_P2P_'.$user_info['userid'];
            $MqttToken = RedisCache::getInstance()->getRedis()->get($MqttKey);
            $MqttTokenTtl = RedisCache::getInstance()->getRedis()->ttl($MqttKey);
            if ($MqttToken && $MqttTokenTtl > 172800) {
                $user_info['mqtoken'] = $MqttToken;
                $user_info['mqusername'] = 'Token|' . $conf['accessKey'] . '|' . $conf['instanceId'];
                $user_info['mqpassword'] = 'R|' . $MqttToken;
                $user_info['mqttl'] = $MqttTokenTtl;
            }else{
                list($resmq,$expireTime) = $this->sendMqtt($MqttKey,$user_info['userid']);
                if (isset($resmq['code']) && $resmq['code'] == 200) {
                    $MqttKey = 'MQTT_P2P_'.$userInfo['userid'];
                    RedisCache::getInstance()->getRedis()->setex($MqttKey,$expireTime,$resmq['tokenData']);
                    $user_info['mqtoken'] = $resmq['tokenData'];
                    $user_info['mqusername'] = 'Token|' . $conf['accessKey'] . '|' . $conf['instanceId'];
                    $user_info['mqpassword'] = 'RW|' . $resmq['tokenData'];
                    $user_info['mqttl'] = $expireTime;
                }else{
                    E("token不存在", 5000);
                    $user_info['mqtoken'] = '';
                    $user_info['mqusername'] = '';
                    $user_info['mqpassword'] = '';
                    $user_info['mqttl'] = 0;
                }
            }
            $user_info['username'] = '';
            $result = [
                "info" => $user_info,
            ];
            $this->returnCode = 200;
            $this->returnMsg = "操作成功";
            $this->returnData = $result;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }

        $this->returnData();

    }

    /*找回密码接口*/
    public function forgotpw($username, $password = null, $vertify = null)
    {
        try {
            $id = D('member')->getByuid($username);
            // var_dump($id);die;
            // var_dump($id);die;
            ParamCheck::checkMobile("username", $username);
            //数据操作(根据用户名查询用户在不在数据中)
            if (!$id) {
                E("该用户不存在", 5002);
            }
            if (!empty($vertify)) {
                //校验验证码是否正确
                $code = RedisCache::getInstance()->get('verify_code_' . $username);
                // $vertifys='verify_code_'.$username;
                //  $code=$_SESSION[$vertifys];
                //var_dump($code);die;
                if ($vertify !== $code) {
                    E("验证码错误", 2002);
                }
            }
            if ($password == null) {
                E("密码不能为空", 2003);
            }
            $where = array('id' => $id);
            $pw = array('password' => md5($password));
            $updatepw = D('member')->updatepw($where, $pw);
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        $this->returnData();
    }

    /*绑定手机号接口*/
    public function bindmobile($username, $vertify, $openid, $payload, $type)
    {
        try {
            $username = $_REQUEST['username'];
            $vertify = $_REQUEST['vertify'];
            $openid = $_REQUEST['openid'];
            $payload = $_REQUEST['payload'];
            $type = $_REQUEST['type'];//qq  wechat
            // $id = D('member')->getByuid($username);
            $type = strtolower($type);
            if (!in_array($type, $this->supported_type)) {
                E('暂不支持的类型', 2000);
            }
            if (in_array($type, array("wechat", "facebook"))) {
                $payload = json_decode($payload, true);
            } else {
                $payload = json_decode(stripcslashes($payload), true);
            }
            if (empty($payload)) {
                E('数据有误,不是正确的json格式！', 2001);
            }
            ParamCheck::checkMobile("username", $username);
            if (!empty($vertify)) {
                //校验验证码是否正确
                $code = RedisCache::getInstance()->get('verify_code_' . $username);
                // var_dump($code);die;
                //$code=$_SESSION[$vertifys];
                if ($vertify !== $code) {
                    E("验证码错误", 2002);
                }
            } else {
                E('验证码不能为空', 2003);
            }
            //校验完之后进行逻辑判断入库操作
            if ($type == "wechat") {
                $user_info_where = array('username' => $username);
                $field = 'id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';
                //检测手机号是否注冊过
                $user_info = D('member')->getByqopenid($user_info_where, $field);
                $user_info = dealnull($user_info[0]);
                //  var_dump($user_info);die;
                //如果注册过
                if ($user_info) {
                    $fields = "wxunionid,qopenid";
                    $wx_qq_id = D('member')->getByqopenid($user_info_where, $fields);
                    // var_dump($wx_qq_id);die;
                    if ($wx_qq_id[0]['wxunionid'] != null || $wx_qq_id[0]['qopenid'] != null) {
                        $result = [
                            'status' => "1",//已经绑定三方账号 是否接触绑定
                            'openid' => $openid,
                            'username' => $username,
                            "info" => [],
                            "payload" => $payload,
                        ];
                    } else {//注册过未绑定过任何三方账户
                        if ($user_info['nickname'] == null) {
                            $data['nickname'] = $payload['nickname'];
                        }
                        if ($user_info['avatar'] == null) {
                            $data['avatar'] = $payload['headimgurl'];
                        }
                        $data['login_time'] = date("Y-m-d H:i:s", time());
                        $data['login_ip'] = gets_client_ip();
                        $data['wxunionid'] = $payload['unionid'];
                        $data['wxopenid'] = $openid;
                        $updata_user = D('member')->updateDate($user_info['userid'], $data);
                        if ($updata_user) {
                            $user_info = D('member')->getByqopenid($user_info_where, $field);
                            $user_info = dealnull($user_info[0]);
                            $result = [
                                'status' => "0",
                                'openid' => "",
                                'username' => "",
                                "info" => $user_info,
                                "payload" => [],
                                //未绑定过手机号
                            ];
                        }
                    }
                } else {//手机号未注册
                    //实例化数据(手机号未注册 绑定微信操作 入库)
                    $time = date('Y-m-d H:i:s', time());
                    // var_dump($time);die;
                    if ($payload['sex'] == "") {
                        $payload['sex'] = "3";
                    }
                    $gets_client_ip = gets_client_ip();
                    $data = [
                        "username" => $username,
                        "nickname" => $payload['nickname'],
                        "avatar" => $payload['headimgurl'],
                        "register_time" => $time,
                        "register_ip" => $gets_client_ip,
                        "login_time" => $time,
                        "login_ip" => $gets_client_ip,
                        'wxunionid' => $payload['unionid'],
                        'wxopenid' => $openid,
                        'sex' => $payload['sex'],
                    ];
                    $user = D('member')->addData($data);
                    if ($user) {
                        $id = D('member')->getByuid($username);
                        $user_info = D('member')->getOneByIdField($id, $field);
                        $user_info = dealnull($user_info[$id]);
                        $result = [
                            'status' => "0",
                            'openid' => "",
                            'username' => "",
                            "info" => $user_info,
                            "payload" => [],
                            //未绑定过手机号
                        ];
                    }
                }
            } elseif ($type == "qq") {
                $user_info_where = array('username' => $username);
                $field = 'id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';
                //检测手机号是否注冊过
                $user_info = D('member')->getByqopenid($user_info_where, $field);
                $user_info = dealnull($user_info[0]);
                //  var_dump($user_info);die;
                //如果注册过
                if ($user_info) {
                    $fields = "wxunionid,qopenid";
                    $wx_qq_id = D('member')->getByqopenid($user_info_where, $fields);
                    // var_dump($wx_qq_id);die;
                    if ($wx_qq_id[0]['wxunionid'] != null || $wx_qq_id[0]['qopenid'] != null) {
                        $result = [
                            'status' => "1",//已经绑定三方账号 是否接触绑定
                            'openid' => $openid,
                            'username' => $username,
                            "info" => [],
                            "payload" => $payload,
                        ];
                    } else {//注册过未绑定过任何三方账户
                        if ($user_info['nickname'] == null) {
                            $data['nickname'] = $payload['nickname'];
                        }
                        if ($user_info['avatar'] == null) {
                            $data['avatar'] = $payload['figureurl'];
                        }
                        $data['login_time'] = date("Y-m-d H:i:s", time());
                        $data['login_ip'] = gets_client_ip();
                        $data['qopenid'] = $openid;
                        $updata_user = D('member')->updateDate($user_info['userid'], $data);
                        if ($updata_user) {
                            $user_info = D('member')->getByqopenid($user_info_where, $field);
                            $user_info = dealnull($user_info[0]);
                            $result = [
                                'status' => "0",
                                'openid' => "",
                                'username' => "",
                                "info" => $user_info,
                                "payload" => [],
                                //未绑定过手机号
                            ];
                        }
                    }
                } else {//手机号未注册
                    //实例化数据(手机号未注册 绑定微信操作 入库)
                    $time = date('Y-m-d H:i:s', time());
                    // var_dump($time);die;
                    $gets_client_ip = gets_client_ip();
                    if ($payload['gender'] == "男") {
                        $payload['gender'] = "1";
                    } elseif ($payload['gender'] == "女") {
                        $payload['gender'] = "2";
                    } else {
                        $payload['gender'] = "3";
                    }
                    $data = [
                        "username" => $username,
                        "nickname" => $payload['nickname'],
                        "avatar" => $payload['headimgurl'],
                        "register_time" => $time,
                        "register_ip" => $gets_client_ip,
                        "login_time" => $time,
                        "login_ip" => $gets_client_ip,
                        'qopenid' => $openid,
                        'sex' => $payload['gender'],
                    ];
                    $user = D('member')->addData($data);
                    if ($user) {
                        $id = D('member')->getByuid($username);
                        $user_info = D('member')->getOneByIdField($id, $field);
                        $user_info = dealnull($user_info[$id]);
                        $result = [
                            'status' => "0",
                            'openid' => "",
                            'username' => "",
                            "info" => $user_info,
                            "payload" => [],
                            //未绑定过手机号
                        ];
                    }
                }
            }
            $this->returnCode = 200;
            $this->returnData = $result;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        $this->returnData();
    }

    /**解除绑定接口
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     * @param $signature    签名md5(小写(token+room_id))
     */
    public function removebind($openid, $username, $type, $payload)
    {
        try {
            $openid = $_REQUEST['openid'];
            $username = $_REQUEST['username'];
            $type = $_REQUEST['type'];
            $payload = $_REQUEST['payload'];
            $type = strtolower($type);
            if (!in_array($type, $this->supported_type)) {
                E('暂不支持的类型', 2000);
            }
            if (in_array($type, array("wechat", "facebook"))) {
                $payload = json_decode($payload, true);
                // var_dump($payload);die;
            } else {
                $payload = json_decode(stripcslashes($payload), true);
            }
            if (empty($payload)) {
                E('数据有误,不是正确的json格式！', 2001);
            }
            ParamCheck::checkMobile("username", $username);
            $field = 'id as userid,username,sex,nickname,intro,status,avatar,birthday,roomnumber';
            $user_info_where = array('username' => $username);
            $id = D('member')->getByuid($username);
            $token = gettoken($id);
            if ($type == "wechat") {
                $data = array('qopenid' => "", 'wxopenid' => $openid, 'wxunionid' => $payload['unionid']);
                $updata_user = D('member')->updateDate($id, $data);
                // var_dump($updata_user);die;
                if ($updata_user) {
                    $user_info = D('member')->getByqopenid($user_info_where, $field);
                    $user_info[0]['token'] = $token;
                    // var_dump($user_info);die;
                    $user_info = dealnull($user_info[0]);
                    $result = [
                        "info" => $user_info,
                        //未绑定过手机号
                    ];
                } else {
                    $user_info = D('member')->getByqopenid($user_info_where, $field);
                    $user_info[0]['token'] = $token;
                    $user_info = dealnull($user_info[0]);
                    $result = [
                        "info" => $user_info,
                        //未绑定过手机号
                    ];
                }
            } elseif ($type == "qq") {
                $data = array('qopenid' => $openid, 'wxopenid' => "", 'wxunionid' => "");
                $updata_user = D('member')->updateDate($id, $data);
                if ($updata_user) {
                    $user_info = D('member')->getByqopenid($user_info_where, $field);
                    $user_info[0]['token'] = $token;
                    $user_info = dealnull($user_info[0]);
                    $result = [
                        "info" => $user_info,
                        //未绑定过手机号
                    ];
                } else {
                    $user_info = D('member')->getByqopenid($user_info_where, $field);
                    $user_info[0]['token'] = $token;
                    $user_info = dealnull($user_info[0]);
                    $result = [
                        "info" => $user_info,
                        //未绑定过手机号
                    ];
                }
            }
            $this->returnCode = 200;
            $this->returnData = $result;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        $this->returnData();

    }

    /*账号内解除绑定接口(关闭按钮)*/
    public function removeinnerbind($token, $type)
    {
        try {
            if ($type == null) {
                E('参数错误', 2000);
            }
            $userid = RedisCache::getInstance()->get($token);
            //var_dump($userid);die;
            //解绑微信或者qq将id清空 很具用户id 获取对应的三方id
            if ($type == 'wechat') {
                $data = array('wxunionid' => '', 'wxopenid' => '');
                D('member')->updateDate($userid, $data);

            } elseif ($type == 'qq') {
                $data = array('qopenid' => '');
                D('member')->updateDate($userid, $data);
            }

            $this->returnCode = 200;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        $this->returnData();
    }

    /**验证是否三方绑定过*/
    public function is_bind($token)
    {
        try {
            if ($token == null) {
                E('参数错误', 2000);
            }
            //根据useerid获取qopenid 和wxopenid
            $userid = RedisCache::getInstance()->get($token);
            $where = array('id' => $userid);
            $field = 'qopenid,wxopenid';
            $thirdmsg = D('member')->getByqopenid($where, $field);
            //var_dump($thirdmsg[0]);die;
            if ($thirdmsg[0]['qopenid'] == "" && $thirdmsg[0]['wxopenid'] == "") {
                $openstatus = "1";//没有绑定过开关全部关闭
            } elseif ($thirdmsg[0]['qopenid'] != "") {
                $openstatus = "2";//qq 开
            } elseif ($thirdmsg[0]['wxopenid'] != "") {
                $openstatus = "3";//微信开
            }
            $result = array(
                'openstatus' => $openstatus,
            );
            $this->returnCode = 200;
            $this->returnData = $result;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        $this->returnData();

    }

    /*主播关闭 打开 粉丝开播提醒按钮*/
    public function fansmenusatatus($token, $type)
    {
        try {
            $userid = RedisCache::getInstance()->get($token);
            if ($type == 0) {//打开开关
                $data = array('fansmenustatus' => 0);
                $update = D('member')->updateDate($userid, $data);

            } elseif ($type == 1) {
                $data = array('fansmenustatus' => 1);
                $update = D('member')->updateDate($userid, $data);
            } else {
                E('更新失败', 2000);
            }
            $this->returnCode = 200;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        $this->returnData();

    }

    /**房间用户信息数据
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     * @param $signature    签名md5(小写(token+room_id))
     */
    public function room_memberinfo($token, $room_id, $user_id, $signature)
    {
        $data = [
            "token" => I('post.token'),
            "room_id" => I('post.room_id'),
            "user_id" => I('post.user_id'),
            "signature" => I('post.signature'),
        ];
        try {
            //检验数据
            ParamCheck::checkInt("room_id", $data['room_id'], 1);
            ParamCheck::checkInt("user_id", $data['user_id'], 1);
            /*if($data['signature'] !== Md5(strtolower($data['token'].$data['room_id']))){
                E("验签失败",2000);
            }*/
            //根据当前用户id查询对应的数据
            $user_info = MemberService::getInstance()->detail($data['user_id']);
            $user_info['avatar'] = C("APP_URL") . $user_info['avatar'];
            //判断当前用户的是否已关注 0未关注 1已关注用户
            $user_info['type'] = 1;
            //判断当前用户是否被禁言 0未禁言 1已禁主
            $isdebar = [
                "user_id" => $data['user_id'],
                "room_id" => $data['room_id'],
                "type" => 2,
            ];
            $member_isdebar = DebarService::getInstance()->isDebar($isdebar);
            if ($member_isdebar) {
                $user_info['is_speak'] = 1;
            } else {
                $user_info['is_speak'] = 0;
            }
            //用户Lv等级数据值
            $user_info['lv_dengji'] = "5";
//            var_dump($user_info);die();
            $user_info = dealnull($user_info);
            $result = [
                "memberinfo" => $user_info,
            ];

            $this->returnCode = 200;
            $this->returnData = $result;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }

        $this->returnData();

    }

    /**
     * 用户个人信息接口:请求方式为POST
     * @param $token    用户token值
     * @param $user_id      用户id
     * @param $signature 签名MD5(小写(token+uid)
     * 返回值说明
     */
    public function user_info($token, $user_id, $device, $signature)
    {
        //获取数据
        $data = [
            "token" => I('post.token'),
            "user_id" => I('post.user_id'),
            "device" => I('post.device'),
            "signature" => I('post.signature'),
        ];

        try {
            //校验数据
            ParamCheck::checkInt("user_id", $data['user_id'], 1);
            $uid = RedisCache::getInstance()->get($data['token']);
            /*if($data['signature'] !== md5(strtolower($data['token'].$data['user_id']))){
                E("验签失败",2000);
            }*/
            $monitoring_info = D('Monitoring')->userMonitoring($data['user_id']); // 用户监控状态
            //用户信息
            $user_info = MemberService::getInstance()->UserDetail($data['user_id']);
            $user_info['username'] = "";
            $user_info['register_time'] = $user_info['register_time'] ? $user_info['register_time'] : '';
            $user_info['avatar'] = C("APP_URL") . $user_info['avatar'];
            $user_info['twelve_animals'] = birthext($user_info['birthday']);
            $is_attention = D("attention")->is_attention($uid, $data['user_id']);
            if ($is_attention) {
                $user_info['type'] = 2;     //已关注用户
            } else {
                $user_info['type'] = 1;     //未关注
            }
            //判断当前用户的是否已拉黑 1未拉黑 1已拉黑用户
            //添加拉黑表
            $blackwhere = [
                'toblack_uid' => $data['user_id'],
                'black_uid' => $uid,
            ];
            $blackRes = D('forum_black')->where($blackwhere)->find();
            if ($blackRes) {
                $user_info['is_blocks'] = 2;
            } else {
                $user_info['is_blocks'] = 1;
            }
//            $Easemob = new Easemob();
//            $Blacklist = $Easemob->getBlacklist($uid);
//            if (in_array($data['user_id'], $Blacklist["data"])) {
//                $user_info['is_blocks'] = 2;
//            } else {
//                $user_info['is_blocks'] = 1;
//            }
            //查询用户登录时间
            $loginTimeIp = RedisCache::getInstance()->HGET('login_time_ip', $uid);
            if ($loginTimeIp) {
                $timeip = explode("_", $loginTimeIp);
                $user_info['login_time'] = $timeip[0];
                $user_info['login_ip'] = $timeip[1];
            } else {
                $user_info['login_time'] = '';
                $user_info['login_ip'] = '';
            }
            $user_info = dealnull($user_info);
            if ($user_info == false) {
                E("该当前用户不存在", 2001);
            }
            //用户轮播图(九张)
            $member_avatar = [];
            // $member_avatar = MemberAvatarService::getInstance()->getUidAvatar($data['user_id']);
            // if($member_avatar){
            //     foreach($member_avatar as $key=>$value){
            //         $member_avatar[$key]['photo_url'] = C("APP_URL").$value['photo_url'];
            //     }
            // }else{
            //     $member_avatar = [];
            // }

            //当前是否在某个房间里(房间id,头像,昵称)
            $room_info = [];
            $roomKey = 'UserCurrentRoom';
            $room_id = RedisCache::getInstance()->getRedis()->hget($roomKey,$data['user_id']);
            $banben = explode(',', $this->clientVersion);
            if(empty($room_id)){
                $room_info = null;
            }else{
                $room_data = LanguageroomService::getInstance()->getDeatil($room_id);
                if (version_compare($banben[0], '2.2.0', '<') && $room_data['room_type'] == 6) {
                    $room_info = null;
                } else {
                    $room_info['room_id'] = $room_id;
                    $room_data = LanguageroomService::getInstance()->getDeatil($room_info['room_id']);
                    $room_info['room_name'] = $room_data['room_name'];    //房间名称
                    $room_info['avatar'] = getavatar(MemberService::getInstance()->getOneByIdField($room_data['user_id'],"avatar"));    //房间创始人的头像\
                }
            }
//            $room_info = [];
//            $room_id = RoomMemberService::getInstance()->getInstance()->findRoom($data['user_id']);
//            $room_info['room_id'] = $room_id;       //房间id
//            $room_data = LanguageroomService::getInstance()->getDeatil($room_info['room_id']);
//            $room_info['room_name'] = $room_data['room_name'];    //房间名称
//            $room_info['avatar'] = C("APP_URL") . MemberService::getInstance()->getOneByIdField($room_data['user_id'], "avatar");    //房间创始人的头像
            //用户等级信息
            //统计当前充值的虚拟币总数量
            $totalcoin = floor(MemberService::getInstance()->getOneByIdField($data['user_id'], "totalcoin"));
            //获取当前充值等级制度
            $grade_listes = D('GradeDiamond')->getlist();
            $grade_list = array_column($grade_listes, 'diamond_needed', 'grade_id');
            arsort($grade_list);        //保持键/值对的逆序排序函数
            $grade_diamond = array_flip($grade_list);   //反转数组
            // $lv_dengji = $this->gradefun($totalcoin,$grade_diamond);
            $lv_dengji = $user_info['lv_dengji'];
//            var_dump($lv_dengji);die();
            $grage_info['lv_dengji'] = $lv_dengji; //当前等级
            $grage_info['lh_dengji'] = $lv_dengji + 1; //当前离下一个等级
            $grage_info['level_number'] = $totalcoin; //当前虚拟币
            $grage_info['hight_number'] = D('GradeDiamond')->getOneByIdField($grage_info['lh_dengji'], "diamond_needed"); //当前下一个等级虚拟币
            $grage_info['level_neednumber'] = D('GradeDiamond')->getOneByIdField($grage_info['lv_dengji'], "diamond_needed"); //当前等级虚拟币
            $grage_info['percentage'] = (($grage_info['level_number'] - $grage_info['level_neednumber']) / ($grage_info['hight_number'] - $grage_info['level_neednumber'])) * 100;
            $grage_info['percentage'] = (int)$grage_info['percentage'];
            //用户关注,粉丝,最近访客信息
            $attention_where = [
                "userid" => $data['user_id'],
            ];
            $attention_count = D('attention')->attentioncount($attention_where);   //用户关注数
            $follower_where = [
                "userided" => $data['user_id'],
            ];
            $follower_count = D('attention')->attentioncount($follower_where);          //粉丝已查看数量
            $follower_new = [
                "userided" => $data['user_id'],
                "status" => 0,
            ];

            $follower_new = D('attention')->attentioncount($follower_new);          //粉丝未查看数量
            $history_count = VisitorMemberService::getInstance()->oldcount($data['user_id']);          //最近访客查看数量
//            $history_new = VisitorMemberService::getInstance()->Newcount($data['user_id']);          //最近访客未查看数量
            //redis 数据
            $visitKeyCount = 'visit_count';
            $history_new = RedisCache::getInstance()->getRedis()->hGet($visitKeyCount, $data['user_id']);
            if (empty($history_new)) {
                $history_new = "0";
            }
            $number_info['attention_count'] = $attention_count;
            $number_info['follower_count'] = $follower_count;
            $number_info['follower_new'] = $follower_new;
            $number_info['history_count'] = $history_count;
            $number_info['history_new'] = $history_new;
            //最近访客数据操作(当前用户只能进行访问一次,只能更新最新时间数据)
            //访客信息记录加入功能(当前id与查看用户id)
            if ($uid !== $data['user_id']) {
                $visitKey = 'visit_user_' . $data['user_id'];
                $visitTimeKey = 'visit_time';
                $timeStr = time();
                RedisCache::getInstance()->getRedis()->ZINCRBY($visitKey, 1, $uid);
                RedisCache::getInstance()->getRedis()->HSET($visitTimeKey, $uid, $timeStr);
                //将被访客的用户数据记录在redis里面 哈希存储
                $visitKeyCount = 'visit_count';
                //检测当前字段是否存在,存在累计加值,不存在设置初始值1
                $is_redis = RedisCache::getInstance()->getRedis()->hExists($visitKeyCount, $data['user_id']);
                if ($is_redis) {      //递增
                    RedisCache::getInstance()->getRedis()->hincrby($visitKeyCount, $data['user_id'], 1);
                } else {      //设置初始值1
                    RedisCache::getInstance()->getRedis()->HSET($visitKeyCount, $data['user_id'], 1);
                }
                // $result = VisitorMemberService::getInstance()->getFind($uid,$data['user_id']);
                // if(empty($result)){
                //     //获取数据
                //     $history['uid'] = $uid;
                //     $history['touid'] = $data['user_id'];
                //     $history['ctime'] = time();
                //     $history['device'] = $data['device'];
                //     $history['access_ip'] = $_SERVER['REMOTE_ADDR'];
                //     $history['status'] = 1;
                //     //加入数据库
                //     D('visitor_member')->add($history);
                // }else{
                //     //修改当前用户访客时间
                //     VisitorMemberService::getInstance()->updateTime($uid,$data['user_id']);
                // }
            }
            //排行榜接口列表(给当前用户当月赠送礼物最多的三个用户头像)
            // $month_begindate=date('Y-m-01 00:00:00');       //当月起始时间 -> 修改成当天(2019-7-30 by yond)
            $month_begindate = date('Y-m-d 00:00:00');
            $month_enddate = date('Y-m-d H:i:s');                              //当前时间
            //统计当前当前起始时间与当前时间的赠送最多的三个用户数据
            $monthcondition = array(
//                "action" => "sendgift",
                "addtime >= '" . $month_begindate . "' and addtime <= '" . $month_enddate . "' and uid != '" . $data['user_id'] . "'",
                "touid" => $data['user_id'],
                'action' => array('in','sendgift,sendgiftFromBag'),
            );
            // $rank_list=D('beandetail')->randmember($monthcondition);
            $rank_list = D('coindetail')->randmember($monthcondition, $limit = 3);
            if ($rank_list) {
                foreach ($rank_list as $keys => &$values) {
                    $rank_listes[$keys]['user_id'] = $values['uid'];
                    // $rank_listes[$keys]['avatar'] = C("APP_URL").MemberService::getInstance()->getOneByIdField($values['get_uid'],"avatar");
                    $rank_listes[$keys]['avatar'] = getavatar($values['avatar']);
                }
            } else {
                $rank_listes = [];
            }
            $giftList = GiftService::getInstance()->getGiftList();
            // $giftList = array_slice($giftList,0,4);
            $black = array('1000009', '1000006');
            if (in_array($data['user_id'], $black)) {
                $gitData = [];
                $gift_num = 0;
            } else {
                list($gitData, $gift_num) = CoindetailService::getInstance()->get_gift($data['user_id'], $giftList, 1);
                $gitData = array_slice($gitData, 0, 4);
            }
            $user_info['username'] = '';
            $result = [
                "user_info" => $user_info,      //用户信息
                "grade_info" => $grage_info,       //等级信息
                "number_info" => $number_info,      //关注,粉丝,最近访客
                "member_avatar" => $member_avatar,      //用户头像列表
                "room_info" => $room_info,             //房间信息
                'monitoring_info' => $monitoring_info,    //用户监控信息
                "rank_list" => $rank_listes,             //排行榜信息列表
                "rank_gift" => $gitData,             //礼物列表
                "gift_num" => $gift_num,             //礼物总个数
            ];

            $this->returnCode = 200;
            $this->returnData = $result;

        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        $this->returnData();

    }

    //用户等级方法
    private function gradefun($gf, $arr)//用户等级函数
    {
        foreach ($arr as $key => $value) {
            if ($gf >= $key) {
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
    public function edit($token, $profile, $signature = null)
    {
        //获取数据
        $data = [
            "token" => I('post.token'),
        ];
        /* $token = I('post.token');
         $profile = I('post.profile');
         $signature = I('post.signature');*/
        try {
            if ($data['token']) {
                $user_info['id'] = RedisCache::getInstance()->get($data['token']);
            } else {
                E("用户TOKEN参数不正确", 2000);
            }
            /* if($signature!== md5(strtolower($token))){
                 E("验签失败",2000);
             }*/
            $profile = stripslashes($profile);  //过滤数据
            $profile = json_decode($profile, true); //将josn转化为数组
            $profile = $this->sanitationProfile($profile);  //设置对应的字段
            $result_keys = array_keys($profile);    //获取修改的键
            $result_values = array_values($profile);    //获取修改的值
            //检验数据昵称
            if ($result_keys[0] == 'nickname') {
                if (empty($result_keys[0])) {
                    E("昵称不能为空", 2000);
                }
                //重复的数据不能修改
                $is_repeat = D('member')->getOneById($user_info['id']); 
                if ($is_repeat['nickname'] == $result_values[0]) {
                    E("该当前用户已重复", 2000);
                }
                //当前用户昵称不能出现重复
                $nickname = D('member')->getNickname($result_values[0]);
                if ($nickname) {
                    E("该用户名昵称已存在", 2000);
                }
                //内容安全功能接口(昵称)
                $textcan = new TextcanController();
                $is_safe = $textcan->textcan($result_values[0]);
                if ($is_safe !== "pass") {
                    E("当前昵称包含色情或敏感字字符", 2008);
                }
            }
            //检验数据简介
            if ($result_keys[0] == 'intro') {
                //内容安全功能接口(简介)
                $textcan = new TextcanController();
                $is_safe = $textcan->textcan($result_values[0]);
                if ($is_safe !== "pass") {
                    E("当前昵称包含色情或敏感字字符", 2008);
                }
//                var_dump($is_safe);die();
            }
            //数据操作
            $userKey = "userinfo_";
            $result = D('member')->updateById($user_info['id'], $result_keys[0], $result_values[0]);
            if ($result) {
                if ($result_keys[0] == 'nickname') {
                    $rdsRoom = RedisCache::getInstance()->getRedis()->hGet('UserCurrentRoom',$user_info['id']);
                    if (!empty($rdsRoom)) {
                        $queue = 'roomid_'.$rdsRoom;
                        // $msg = ['uid'=>$user_info['id'],'avatar'=>C('APP_URL_image').$is_repeat['avatar'],'nickname'=>$is_repeat['nickname']];
                        $msg = ['uid'=>$user_info['id'],'nickname'=>$result_values[0]];
                        $msg = json_encode($msg);
                        PushMsgService::getInstance()->send($queue,$msg);
                    }
                }
                //修改redis里面对应的用户字段
                RedisCache::getInstance()->hSet($userKey . $user_info['id'], $result_keys[0], $result_values[0]);
            }
            if ($result_keys[0] == "birthday") {
                $user_info['birthday'] = MemberService::getInstance()->getOneByIdField($user_info['id'], "birthday");
                $user_info['twelve_animals'] = birthext($user_info['birthday']);
                $result = [
                    "user_info" => $user_info,      //用户信息
                ];
                $this->returnData = $result;
            }
            $this->returnCode = 200;
//            if($result){
//                $this -> returnCode = 200;
//            }else{
//                E("用户修改失败",2000);
//            }
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
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }

        $this->returnData();
    }

    /**
     * 净化用户输入的个人资料.
     * @param array $profile .
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
    public function complain($token, $to_uid, $contents, $signature)
    {
        $data = [
            "token" => I('post.token'),
            "to_uid" => I('post.to_uid'),
            "contents" => I("post.contents"),
            "signature" => I('post.signature'),
        ];
        try {
            //校验数据
            ParamCheck::checkInt("to_uid", $data['to_uid'], 1);
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
            $create_time = date("Y-m-d", $res['create_time']);
            $datatime = date("Y-m-d", time());
            if ($datatime !== $create_time) {
                $addData = [
                    "user_id" => $user_info['uid'],
                    "to_uid" => $data['to_uid'],
                    "contents" => $data['contents'],
                    "create_time" => time(),
                ];
                D("complaints")->add($addData);
            } else {
                E("您已举报过此用户", 2000);
            }
            $this->returnCode = 200;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }

        $this->returnData();
    }

    /**拉黑接口
     * @param $token    token值
     * @param $user_id  用户id
     * @param $signature    签名(md5(strtolower(token+user_id))
     */
    public function defriend($token, $user_id, $signature = null)
    {
        //获取数据
        $data = [
            "token" => I('post.token'),
            "user_id" => I('post.user_id'),
            "signature" => I('post.signature'),
        ];
        try {
            $user_info['uid'] = RedisCache::getInstance()->get($data['token']);
            //验签数据
            // if($data['signature']!== md5(strtolower($data['token'].$data['user_id']))){
            //     E("验签失败",2000);
            // }
            //检查当前用户是否存在
            $check_userid = D('member')->detailUser($data['user_id']);
            if(empty($check_userid)){
                E("该用户不存在",2009);
            }
            //环信操作拉黑
            $Easemob = new Easemob();
            //检测当前用户是否已拉黑操作
            // $Blacklist = $Easemob->getBlacklist($user_info['uid']);
            // if (in_array($data['user_id'], $Blacklist["data"])) {
            //     E("该用户已被拉黑", 2000);
            // }
            //拉黑用户操作
            $usernames = array(
                "usernames" => array($data['user_id'])        //这里是一个二维数组
            );
            // $Easemob->addUserForBlacklist($user_info['uid'], $usernames);

            //添加拉黑表
            $blackRes = D('forum_black')->getOne(array('toblack_uid' => $data['user_id'], 'black_uid' => $user_info['uid']), 'id,toblack_uid,black_uid');
            if ($blackRes) {
                E("该用户已被拉黑", 2000);
            } else {
                $time = time();
                $blackParam = array('toblack_uid' => $data['user_id'], 'black_uid' => $user_info['uid'], 'createtime' => $time, 'updatetime' => $time);
                $blackData = D('forum_black')->addData($blackParam);
            }
            $this->returnCode = 200;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }

        $this->returnData();
    }

    /**黑名单列表
     * @param $token    token值
     */
    public function listBlack($token)
    {
        $token = $_REQUEST['token'];
        try {
            $id = RedisCache::getInstance()->get($token);
            //环信操作拉黑
            $Easemob = new Easemob();
            //检测当前用户是否已拉黑操作
            $Blacklist = $Easemob->getBlacklist($id);
            $ids = $Blacklist['data']; //获取此用户的黑名单列表
            //查询该用户数据
            $user_info = D('member')->userList($ids);
            if ($user_info) {
                $this->returnCode = 200;
                $this->returnData = $user_info;
            } else {
                $this->returnCode = 201;
                $this->returnMsg = "没有人在黑名单";
            }

        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }
        $this->returnData();
    }

    /**取消拉黑接口
     * @param $token    token值
     * @param $user_id  用户id
     * @param null $signature 签名(md5(strtolower(token+user_id))
     */
    public function blackout($token, $user_id, $signature = null)
    {
        //获取数据
        $data = [
            "token" => I('post.token'),
            "user_id" => I('post.user_id'),
            "signature" => I('post.signature'),
        ];
        try {
            $user_info['uid'] = RedisCache::getInstance()->get($data['token']);
            //验签数据
            /*if($data['signature']!== md5(strtolower($data['token'].$data['user_id']))){
                E("验签失败",2000);
            }*/
            //检查当前用户是否存在
            $check_userid = D('member')->detailUser($data['user_id']);
            if(empty($check_userid)){
                E("该用户不存在",2009);
            }
            //环信操作拉黑
            $Easemob = new Easemob();
            //检测当前用户是否在拉黑列表里操作
            // $Blacklist = $Easemob->getBlacklist($user_info['uid']);
            // if (!in_array($data['user_id'], $Blacklist["data"])) {
            //     E("该用户不存在", 2000);
            // }
            //取消拉黑用户操作
            // $Easemob->deleteUserFromBlacklist($user_info['uid'], $data['user_id']);
            //添加拉黑表
            $where = [
                'toblack_uid' => $data['user_id'],
                'black_uid' => $user_info['uid']
            ];
            $blackRes = D('forum_black')->del($where);
            $this->returnCode = 200;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }

        $this->returnData();
    }

    /**用户头像上传接口
     * @param $token token值
     * @param $avatar   avatar上传图片的详情
     * @param $avatarid  修改头像(多张)
     */
    public function avatares($token, $avatarid = null)
    {
//        include_once "./ThinkPHP/Library/Vendor/OSS/autoload.php";
//        include_once "./ThinkPHP/Library/Vendor/OSS/src/OSS/OssClient.php";
        $data['token'] = I('post.token');
        $user_info['id'] = RedisCache::getInstance()->get($data['token']);
//        var_dump($user_info['id']);die();
        //获取图片
        if ($_FILES["avatar"]["error"] != 0) {
            $this->error("上传图片有误");
            die();
        }
        // 处理图片
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 1024 * 1024 * 10;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './Public';
        $upload->savePath = '/Uploads/user/'; // 设置附件上传目录
        vendor('OSS.autoload');
        $ossConfig = C('OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID'];//阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET'];//阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT'];//阿里云OSS 地址
        $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间

        // 上传文件
        $info = $upload->upload();
        if (!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
            die();
        } else {// 上传成功
            $dir = "dir1";
            foreach ($info as $k => $v) {
                $object = $dir . '/' . $v['name'];//想要保存文件的名称
                //这个数组是存上传成功以后返回的访问路径，多文件时使用implode函数将其组合

                $downlink[] = $bucket . '.' . $endpoint . '/' . $object;
                $file = './Public' . $info[$k]['savepath'] . $v['savename'];//本地文件路径
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
        try {
            $user_info['id'] = RedisCache::getInstance()->get($data['token']);
            //数据操作
            $result = D('member')->updateById($user_info['id'], "avatar", $resultimage['oss-request-url']);
            $this->returnCode = 200;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }

        $this->returnData();
    }

    /**用户头像上传接口
     * @param $token token值
     * @param $avatar   avatar上传图片的详情
     * @param $avatarid  修改头像(多张)
     */
    public function avatar($token, $avatarid = null)
    {
//        include_once "./ThinkPHP/Library/Vendor/OSS/autoload.php";
//        include_once "./ThinkPHP/Library/Vendor/OSS/src/OSS/OssClient.php";
        $data['token'] = I('post.token');
        $user_info['id'] = RedisCache::getInstance()->get($data['token']);
//        var_dump($user_info['id']);die();
        //获取图片
        if ($_FILES["avatar"]["error"] != 0) {
            $this->error("上传图片有误");
            die();
        }
        // 处理图片
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 1024 * 1024 * 10;// 设置附件上传大小
        $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
        $upload->rootPath = './Public';
        $upload->savePath = '/Uploads/user/'; // 设置附件上传目录
        vendor('OSS.autoload');
        $ossConfig = C('OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID'];//阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET'];//阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT'];//阿里云OSS 地址
        $ossClient = new \OSS\OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间

        // 上传文件
        $info = $upload->upload();
        $url = '/Public' . $info["avatar"]["savepath"] . $info["avatar"]["savename"];
//         $textimg = new TextcanimgController();
// //        $is_safe = TextcanimgController::textSamples($url);
//         $is_safe = $textimg->textSamples($url);
//         $is_safes = explode(",",$is_safe);
//         if($is_safes[0] !== "pass"){
//             echo json_encode(array('code'=>2006,'desc'=>"该用用户图片存在不合法信息"));
//             $base_names = $_SERVER['DOCUMENT_ROOT'];        //获取网站根目录
//             $url = 'Public'.$info["avatar"]["savepath"].$info["avatar"]["savename"];
//             unlink($base_names.$url);
//             die();
//         }else if($is_safes[1] !== "pass"){
//             echo json_encode(array('code'=>2009,'desc'=>"当前图片存在违规哦"));
//             $base_names = $_SERVER['DOCUMENT_ROOT'];
//             $url = 'Public'.$info["avatar"]["savepath"].$info["avatar"]["savename"];
//             unlink($base_names.$url);
//             die();
//         }
//        var_dump($is_safes);die();
        if (!$info) {// 上传错误提示错误信息
            $this->error($upload->getError());
            die();
        } else {// 上传成功
            $dir = "dir1";
            foreach ($info as $k => $v) {
//                $object  = $dir . '/' . $v['name'];//想要保存文件的名称
                $object = $dir . '/' . $user_info['id'] . '_' . $v['savename'];//想要保存文件的名称
                //这个数组是存上传成功以后返回的访问路径，多文件时使用implode函数将其组合

                $downlink[] = $bucket . '.' . $endpoint . '/' . $object;
                $file = './Public' . $info[$k]['savepath'] . $v['savename'];//本地文件路径

                try {

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
        try {
            $user_info['id'] = RedisCache::getInstance()->get($data['token']);
            //数据操作
//            $result = D('member')->updateById($user_info['id'],"avatar",$resultimage['oss-request-url']);
            $result = D('member')->updateById($user_info['id'], "avatar", "/" . $object);
            RedisCache::getInstance()->getRedis()->hset('userinfo_'.$user_info['id'],'avatar','/'.$object);
            $this->returnCode = 200;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }

        $this->returnData();
    }

    /**粉丝贡献列表数据
     * @param $token    token值
     * @param $status 1日2周3月
     * @param null $signature 签名(md5(strtolower(token))
     */
    public function rank_list($token, $status, $signature = null)
    {
        //获取数据值
        $data = [
            "token" => I('post.token'),
            "status" => I('post.status'),
            "signature" => I('post.signature'),
        ];
        try {
            $user_info['uid'] = RedisCache::getInstance()->get($data['token']);
            //验签数据
            /*if($data['signature']!== md5(strtolower($data['token'].$data['user_id']))){
                E("验签失败",2000);
            }*/
            $limit = "50";
            if ($data['status'] == 1) {   //今天数据
                $day_begindate = date('Y-m-d 00:00:00');       //今天起始时间
                $day_enddate = date('Y-m-d 59:59:59');                              //今天结束时间
                //统计当前当前起始时间与当前时间的赠送最多的三个用户数据
                $daycondition = array(
                    "addtime >= '" . $day_begindate . "' and addtime <= '" . $day_enddate . "' and uid != '" . $user_info['uid'] . "'",
                    "touid" => $user_info['uid'],
                    'action' => array('in','sendgift,sendgiftFromBag'),
                );
                $rank_list = D('coindetail')->randmember($daycondition, $limit);
            } else if ($data['status'] == 2) { //最近一周数据(最近一周数据)
                //最近一周
                // $now = time();
                // $resultweek = [];
                // for($i=0;$i<7;$i++){
                //     $resultweek[] = date('Y-m-d',strtotime('-'.$i.' day', $now));
                // }
                // $week_begindate=array_pop($resultweek)." "."00:00:00";       //最近一周数据
                $week_begindate = date('Y-m-d H:i:s', strtotime(date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600))));
                $week_enddate = date('Y-m-d H:i:s', time());                             //最近一周数据
                //统计当前当前起始时间与当前时间的赠送最多的三个用户数据
                $weekcondition = array(
                    "addtime >= '" . $week_begindate . "' and addtime <= '" . $week_enddate . "' and uid != '" . $user_info['uid'] . "'",
                    "touid" => $user_info['uid'],
//                    'action' => 'sendgift',
                    'action' => array('in','sendgift,sendgiftFromBag'),
                );
                $rank_list = D('coindetail')->randmember($weekcondition, $limit);
            } else if ($data['status'] == 3) {     //前一月数据(当前时间前一号到现在的时间)
                $month_begindate = date('Y-m-01 00:00:00');       //当月起始时间
                $month_enddate = date('Y-m-d H:i:s');                              //当前时间
                //统计当前当前起始时间与当前时间的赠送最多的三个用户数据
                $monthcondition = array(
                    "addtime >= '" . $month_begindate . "' and addtime <= '" . $month_enddate . "' and uid != '" . $user_info['uid'] . "'",
                    "touid" => $user_info['uid'],
//                    'action' => 'sendgift',
                    'action' => array('in','sendgift,sendgiftFromBag'),
                );
                $rank_list = D('coindetail')->randmember($monthcondition, $limit);
            }
            if ($rank_list) {
                foreach ($rank_list as $keys => $values) {
                    $rank_listes[$keys]['user_id'] = $values['uid'];
                    $rank_listes[$keys]['avatar'] = C("APP_URL") . $values['avatar'];
                    $rank_listes[$keys]['nickname'] = $values['nickname'];
                    $rank_listes[$keys]['sex'] = $values['sex'];
                    $rank_listes[$keys]['coin'] = $values['coin'];

                    $rank_listes[$keys]['dukename'] = '';
                    $rank_listes[$keys]['user_lv'] = $values['lv_dengji'];
                    $rank_listes[$keys]['vip_lv'] = 1;
                }
            } else {
                $rank_listes = [];
            }
            $this->returnCode = 200;
            $this->returnMsg = "操作成功";
            $this->returnData = $rank_listes;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();

        }
        $this->returnData();
    }

    /**背包列表功能接口
     * @param $token    token值
     * @param $signature    验签 md5(strtolower(token))
     */
    public function Mepack_list($token, $signature)
    {
        //获取数据
        $data = [
            "token" => I('post.token'),
            "signature" => I('post.signature'),
        ];

        try {
            $user_info['uid'] = RedisCache::getInstance()->get($data['token']);
            //验签数据
            /*if($data['signature']!== md5(strtolower($data['token']))){
                E("验签失败",2000);
            }*/
            //查询该背包列表数据
            $list = D('Pack')->getList($user_info['uid']);
            if ($list) {
                foreach ($list as $key => $value) {
                    $list[$key]['gift_image'] = C('APP_URL_image') . $value['gift_image'];
                }
            } else {
                $list = [];
            }
            $this->returnCode = 200;
            $this->returnData = $list;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }

        $this->returnData();
    }

    /**获取锤子单价接口
     * @param $token        token值
     * @param $signature    验签 md5(strtolower(token))
     * @param $type         类型1 金蛋 2彩蛋
     */
    public function Unit_price($token,$type=null,$signature)
    {
        //获取数据
        $data = [
            "token" => I('post.token'),
            "type" => I("post.type"),
            "signature" => I('post.signature'),
        ];
        try {
            //验签数据
            if ($data['signature'] !== md5(strtolower($data['token']))) {
//                E("验签失败",2000);
            }
            //获取单价功能
            $list = D('siteconfig')->find();
            $gift_list = D("gift")->getlistgift($data['type']);
            foreach ($gift_list as $key => $value) {
                $gift_list[$key]['gift_image'] = C('APP_URL_image') . $value['gift_image'];
            }
            $result = [
                "unit_price" => $list['hammer_prices'],
                "color_price" => $list['smashegg_prices'],
                "max_packnum" => $list['max_packnum'],
                "gift_list" => $gift_list,
            ];
            $this->returnCode = 200;
            $this->returnData = $result;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }

        $this->returnData();

    }

    /**砸金蛋与砸彩蛋说明接口
     * @param $token        token值
     * @param $signature    验签 md5(strtolower(token))
     */
    public function smashed_egg($token,$signature){
        //获取数据
        $data = [
            "token" => I('get.token'),
            "signature" => I('get.signature'),
        ];
        try {
            //验签数据
            if ($data['signature'] !== md5(strtolower($data['token']))) {
//                E("验签失败",2000);
            }
            //获取单价功能
            $siteconfig = D('siteconfig')->find();
            $gift_list = D("gift")->getlistgiftegg();
            $result = [
                'one_egg'=>['price'=>$siteconfig['hammer_prices'],'list'=>[]],
                'color_egg'=>['price'=>$siteconfig['smashegg_prices'],'list'=>[]],
            ];

            $goldegg = [];
            $coloregg = [];
            foreach($gift_list as $key=>$value){
                if($value['gift_image']){
                    $value['gift_image'] = C("APP_URL_image").$value['gift_image'];
                }
                //判官砸金蛋
                if($value['one_weight'] > 0 && count($goldegg) < 6){
                    $goldegg[] = $value;
                }
                if($value['color_weight'] > 0 && count($coloregg) < 6){
                    $coloregg[] = $value;
                }
                // if(count($goldegg) > 5 && count($coloregg) > 5){
                //     break;
                // }
            }
            $result["one_egg"]['list'] = $goldegg ? $goldegg : [];
            $result["color_egg"]['list'] = $coloregg ? $coloregg : [];
            $this->returnCode = 200;
            $this->returnData = $result;
        } catch (Exception $e) {
            $this->returnCode = $e->getCode();
            $this->returnMsg = $e->getMessage();
        }

        $this->returnData();

    }

}


?>
