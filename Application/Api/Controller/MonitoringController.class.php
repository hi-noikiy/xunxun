<?php
namespace Api\Controller;

use Api\Service\MemberAvatarService;
use Api\Service\MonitoringService;
use Common\Util\RedisCache;
use Common\Util\ParamCheck;
use Db\Exception;
use Think\Log;

class MonitoringController extends BaseController {

    //执行缓存
    public function setredismonittoring()
    {
        $result = D('Monitoring')->selectAll();
        foreach ($result as $key => $value) {
            $rdsKey = 'monitoring_'.$value['user_id'];
            $rdsVal = ['monitoring_status'=>$value['monitoring_status'],'parents_status'=>$value['parents_status']];
            RedisCache::getInstance()->getRedis()->hMset($rdsKey,$rdsVal);
        }
        echo "done";
    }


    /**
     * 检查用户是否开启了青少年模式的状态方法
     * @param string $token 用户的token = 691243b3dc9d147b4c8c0b0475967344
     */
    public function checkMoStatus($token){
        $token = $_REQUEST['token'];
        try{
             $user_id = RedisCache::getInstance()->get($token);
             $where = ['user_id'=>$user_id];
             $result = D('Monitoring')->idFind($where);
             if(isset($result['monitoring_pwd'])){
                 if($result['monitoring_status'] == 1){
                     $this -> returnCode = 203;
                     $this -> returnData="用户已开启青少年模式模式";
                 }else{
                     $this -> returnCode = 202;
                     $this -> returnData="老用户，跳入输入密码";
                 }
             }else{
                $this -> returnCode = 201;
                $this -> returnMsg="新用户，跳入设置密码";
             }
        } catch (Exception $e) {
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 检查用户是否开启了家长模式
     * @param string $token 用户的token
     */
    public function checkParStatus($token){
        //获取用户id
        $token = $_REQUEST['token'];
        try{
            $user_id = RedisCache::getInstance()->get($token);
            $where = ['user_id'=>$user_id];
            $result = D('Monitoring')->idFind($where);
            if(!empty($result['parents_pwd'])){
                if($result['parents_status'] == 1){
                    $this -> returnCode = 203;
                    $this -> returnData="用户已开启家长模式";
                }else{
                    $this -> returnCode = 202;
                    $this -> returnData="老用户，跳入输入密码";
                }
            }else{
                $this -> returnCode = 201;
                $this -> returnMsg="新用户，跳入设置密码";
            }
        } catch (Exception $e) {
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 青少年模式 设置密码接口
     * @param string $token 用户的token参数
     * @param int $monitoring_pwd 密码
     */
    public function setChildPwd($token,$monitoring_pwd){
        //获取用户id
        $value = [
            'token'=>$token,
            'monitoring_pwd'=>md5($monitoring_pwd),
        ];
        try {
             $user_id = RedisCache::getInstance()->get($token);
            if($value['monitoring_pwd'] == ""){
                E('密码字段不能为空');
            }else{
                $where = ['user_id'=>$user_id];
                $result = D('Monitoring')->idFind($where);
//                var_dump($result);die;
                if($result){
                    $data = [
                        'monitoring_pwd' => $value['monitoring_pwd'],
                        'monitoring_status' => 1,
                        'lock_time'=>date('Y-m-d H:i:s',time()),
                    ];
                    $res = D('Monitoring')->updatepwd($where, $data);
                    if($res){
                        $rdsKey = 'monitoring_'.$user_id;
                        RedisCache::getInstance()->getRedis()->hset($rdsKey,'monitoring_status',1);
                        $lock_time = date('Y-m-d H:i:s',time());
                        $result = [
                            'lock_time' => $lock_time
                        ];
                            $this->returnCode = 200;
                            $this->returnData = $result;
                    }else{
                        $this -> returnCode = 201;
                        $this -> returnMsg="设置密码失败";
                    }
                }else{
                    //添加用户信息
                    $data = [
                        'user_id'=>$user_id,
                        'monitoring_pwd'=>$value['monitoring_pwd'],
                        'monitoring_status'=>1,
                        'lock_time'=>date('Y-m-d H:i:s',time()),
                    ];
                    $res = D('Monitoring')->addData($data);
                    if($res){
                        $rdsKey = 'monitoring_'.$user_id;
                        RedisCache::getInstance()->getRedis()->hset($rdsKey,'monitoring_status',1);
                        $lock_time = date('Y-m-d H:i:s',time());
                        $result = [
                            'lock_time' => $lock_time
                        ];
                        $this->returnCode = 200;
                        $this->returnData = $result;
                    }else{
                        $this -> returnCode = 201;
                        $this -> returnMsg="设置密码失败";
                    }
                }
            }
        } catch (Exception $e) {
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 青少年模式 修改密码前检查接口
     * @param string $token 用户的token参数
     * @param int $monitoring_pwd 密码
     * @param int $old_monitoring_pwd 老密码 可选
     */
    public function upCheckMoPwd($token,$monitoring_pwd){
        $value = [
            'token'=>$token,
            'monitoring_pwd'=>md5($monitoring_pwd)
        ];
        try{
            $user_id = RedisCache::getInstance()->get($token);
            $where = [
                'user_id'=>$user_id,
                'monitoring_pwd'=>$value['monitoring_pwd'],
            ];
            $res = D('Monitoring')->idFind($where);
            if($res){
                $this -> returnCode = 200;
                $this -> returnData="密码正确";
            }else{
                $this -> returnCode = 201;
                $this -> returnMsg="密码不正确";
            }
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 青少年模式 修改密码接口
     * @param string $token 用户的token参数
     * @param int $monitoring_pwd 密码
     */
    public function upChildPwd($token,$monitoring_pwd){
        $value = [
            'token'=>$token,
            'monitoring_pwd'=>md5($monitoring_pwd)
        ];
        try{
            $user_id = RedisCache::getInstance()->get($token);
            if($value['token'] == "" || $value['monitoring_pwd'] == ""){
                $this -> returnCode = 203;
                $this -> returnMsg="新密码旧密码一致";
            }
            $where = ['user_id'=>$user_id];
            $user = D('Monitoring')->idFind($where);
            if($user){
                $data = [
                'monitoring_pwd' => $value['monitoring_pwd'],
                'monitoring_status' => 1,
                ];
                $list= D('Monitoring')->updatepwd($where, $data);
                if ($list) {
                    $rdsKey = 'monitoring_'.$user_id;
                    RedisCache::getInstance()->getRedis()->hset($rdsKey,'monitoring_status',1);
                    $this->returnCode = 200;
                    $this -> returnMsg="修改成功";
                } else {
                    $this -> returnCode = 202;
                    $this -> returnMsg="修改密码不成功";
                }
            }else{
                $this -> returnCode = 201;
                $this -> returnMsg="用户不存在";
            }
        } catch (Exception $e) {
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 家长模式 设置密码接口
     * @param string $token 用户的token参数
     * @param int parents_pwd 密码
     * @param int $old_parents_pwd 老密码 可选
     */
    public function setParentPwd($token,$parents_pwd){
        //获取用户id
        $value = [
            'token'=>$token,
            'parents_pwd'=>md5($parents_pwd),
        ];
        //查询密码是否存在如果存在  并且判断老密码是否正确如果不存在 直接入库新密码
        try {
            $user_id = RedisCache::getInstance()->get($token);
            if($value['parents_pwd'] == ""){
                E('密码字段不能为空');
            }
            $where = ['user_id'=>$user_id];
            $result = D('Monitoring')->idFind($where);
            if($result){
                $data = [
                    'parents_pwd' => $value['parents_pwd'],
                    'parents_status' => 1,
                ];
                $result = D('Monitoring')->updatepwd($where, $data);
                if($result){
                    $rdsKey = 'monitoring_'.$user_id;
                    RedisCache::getInstance()->getRedis()->hset($rdsKey,'parents_status',1);
                    $this -> returnCode = 200;
                    $this -> returnData="设置密码成功，进入并开启家长模式";
                }else{
                    $this -> returnCode = 201;
                    $this -> returnMsg="设置密码失败";
                }
            }else{
                //添加用户信息
                $data = [
                    'user_id'=>$user_id,
                    'parents_pwd'=>$value['parents_pwd'],
                    'parents_status'=>1,
                ];
                $result = D('Monitoring')->addData($data);
                if($result){
                    $rdsKey = 'monitoring_'.$user_id;
                    RedisCache::getInstance()->getRedis()->hset($rdsKey,'parents_status',1);
                    $this -> returnCode = 200;
                    $this -> returnData="设置密码成功，进入并开启家长模式";
                }else{
                    $this -> returnCode = 201;
                    $this -> returnMsg="设置密码失败";
                }
            }
        } catch (Exception $e) {
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 家长模式 修改前检查密码接口
     * @param string $token 用户的token参数
     * @param int $monitoring_pwd 密码
     */
    public function upCheckPaPwd($token,$parents_pwd){
        $value = [
            'token'=>$token,
            'parents_pwd'=>md5($parents_pwd)
        ];
        try{
            $user_id = RedisCache::getInstance()->get($token);
            $where = [
                'user_id'=>$user_id,
                'parents_pwd'=>$value['parents_pwd'],
            ];
            $res = D('Monitoring')->idFind($where);
            if($res){
                $this -> returnCode = 200;
                $this -> returnData="密码正确";
            }else{
                $this -> returnCode = 201;
                $this -> returnMsg="密码不正确";
            }
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 家长模式 修改密码接口
     * @param string $token 用户的token参数
     * @param int $monitoring_pwd 密码
     */
    public function upParentPwd($token,$parents_pwd){
        $value = [
            'token'=>$token,
            'parents_pwd'=>md5($parents_pwd)
        ];
        try{
            $user_id = RedisCache::getInstance()->get($token);
            if($value['parents_pwd']=="" || $value['token']==""){
                $this -> returnCode = 203;
                $this -> returnMsg="新密码旧密码一致";
            }
            $where = ['user_id'=>$user_id];
            $user = D('Monitoring')->idFind($where);
            if($user){
                $data = [
                    'parents_pwd' => $value['parents_pwd'],
                    'parents_status' => 1,
                ];
                $result = D('Monitoring')->updatepwd($where, $data);
                if ($result) {
                    $rdsKey = 'monitoring_'.$user_id;
                    RedisCache::getInstance()->getRedis()->hset($rdsKey,'parents_status',1);
                    $this->returnCode = 200;
                    $this->returnData = "修改密码成功,进入并开启家长模式";
                } else {
                    $this -> returnCode = 202;
                    $this -> returnMsg="修改密码不成功";
                }
            }else {
                $this->returnCode = 201;
                $this->returnMsg = "用户不存在";
            }
        } catch (Exception $e) {
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 判断青少年密码接口
     * @param string $token 用户的token参数
     * @param int $monitoring_pwd 密码
     */
    public function checkMoPwd($token,$monitoring_pwd){
        $value = [
            'token'=>$token,
            'monitoring_pwd'=>md5($monitoring_pwd)
        ];
        try{
            $user_id = RedisCache::getInstance()->get($token);
            $where = [
                'user_id'=>$user_id,
                'monitoring_pwd'=>$value['monitoring_pwd'],
            ];
            $res = D('Monitoring')->idFind($where);
            if($res){
                $lock_time = date("Y-m-d H:i:s", time());
                $data = [
                    'lock_time'=>$lock_time,
                    'monitoring_status'=>1,
                ];
                $result = D('Monitoring')->updateDate($user_id,$data);
                if($result){
                    $rdsKey = 'monitoring_'.$user_id;
                    RedisCache::getInstance()->getRedis()->hset($rdsKey,'monitoring_status',1);
                    $this -> returnCode = 200;
                    $this -> returnData="密码正确,已开启青少年模式";
                }else{
                    E('修改时间状态未成功');
                }
            }else{
                $this -> returnCode = 201;
                $this -> returnMsg="密码不正确";
            }
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 判断家长模式密码接口
     * @param string $token 用户的token参数
     * @param int $monitoring_pwd 密码
     */
    public function checkPaPwd($token,$parents_pwd){
        $value = [
            'token'=>$token,
            'parents_pwd'=>md5($parents_pwd)
        ];
        try{
            $user_id = RedisCache::getInstance()->get($token);
            $where = [
                'user_id'=>$user_id,
                'parents_pwd'=>$value['parents_pwd'],
            ];
            $res = D('Monitoring')->idFind($where);
            if($res){
                $data = [
                    'parents_status' => 1,
                ];
                $result = D('Monitoring')->updateDate($user_id, $data);
                if ($result) {
                    $rdsKey = 'monitoring_'.$user_id;
                    RedisCache::getInstance()->getRedis()->hset($rdsKey,'parents_status',1);
                    $this->returnCode = 200;
                    $this->returnData = "密码正确,已开启家长模式";
                }else{
                    $this->returnCode = 201;
                    $this->returnMsg = "修改状态不成功";
                }
            }else {
                $this->returnCode = 201;
                $this->returnMsg = "密码不正确";
            }
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 接收密码 关闭青少年模式
     * 参数说明:请求方式为post
     * @param  string token  用户ID
     * @param int $monitoring_pwd 密码
     * 返回值说明:
     */
    public function closeMonitoring($token,$monitoring_pwd){
        $value = [
            'token'=>$token,
            'monitoring_pwd'=>md5($monitoring_pwd)
        ];
        try{
            $user_id = RedisCache::getInstance()->get($token);
            $where = [
                'user_id'=>$user_id,
                'monitoring_pwd'=>$value['monitoring_pwd']
            ];
            $res = D('Monitoring')->idFind($where);
            if($res){
                if($res['monitoring_status'] == 0){
                    $this -> returnCode = 201;
                    $this -> returnMsg="青少年模式已关闭，请勿重复操作";
                }else{
                    $data = [
                        'monitoring_status'=>0,
                    ];
                    $result = D('Monitoring')->updateDate($user_id,$data);
                    if($result){
                        $rdsKey = 'monitoring_'.$user_id;
                        RedisCache::getInstance()->getRedis()->hset($rdsKey,'monitoring_status',0);
                        $this -> returnCode = 200;
                        $this -> returnData="关闭青少年模式成功";
                    }else{
                        $this -> returnCode = 201;
                        $this -> returnMsg="关闭青少年模式失败";
                    }
                }
            }else{
                $this -> returnCode = 202;
                $this -> returnMsg="密码错误，关闭青少年模式失败";
            }
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 接收密码 关闭家长模式
     * 参数说明:请求方式为post
     * @param  string token  用户ID
     * @param int $parents_pwd 密码
     * 返回值说明:
     */
    public function closeParent($token,$parents_pwd){
        $value = [
            'token'=>$token,
            'parents_pwd'=>md5($parents_pwd)
        ];
        try{
            $user_id = RedisCache::getInstance()->get($token);
            $where = [
                'user_id'=>$user_id,
                'parents_pwd'=>$value['parents_pwd'],
            ];
            $res = D('Monitoring')->idFind($where);
            if($res){
                if($res['parents_status']==0){
                    $this -> returnCode = 203;
                    $this -> returnMsg="家长模式已关闭，请勿重复操作";
                }else{
                    $data = [
                        'parents_status'=>0,
                    ];
                    $result = D('Monitoring')->updateDate($user_id,$data);
                    if($result){
                        $rdsKey = 'monitoring_'.$user_id;
                        RedisCache::getInstance()->getRedis()->hset($rdsKey,'parents_status',0);
                        $this -> returnCode = 200;
                        $this -> returnData="关闭家长模式成功";
                    }else{
                        $this -> returnCode = 201;
                        $this -> returnMsg="关闭家长模式失败";
                    }
                }
            }else{
                $this -> returnCode = 202;
                $this -> returnMsg="密码错误，关闭家长模式失败";
            }
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 青少年时间锁
     * @param string $token 用户的token参数
     */
    public function checkLock($token){
        $token = $_REQUEST['token'];
        try {
            $user_id = RedisCache::getInstance()->get($token);
            $where = ['user_id'=>$user_id];
            $res = D('Monitoring')->idFind($where);
            if($res){
                if($res['monitoring_status'] == 1){
                    $now = intval(time()/60);
                    $lock_time = intval(strtotime($res['lock_time'])/60);
                    $lock = $res['lock_time'];//用戶最后上锁時間//普通
                    $old_time = substr($lock,0,10); //轉換格式
                    $new_time = date('Y-m-d'); //今天時間
                    if($new_time == $old_time){ //判断上锁时间是否是今天
                        if($now-$lock_time>40){//判断时间是否大于40分钟
                            $this -> returnCode = 201;
                            $this -> returnMsg="时间已过期";
                        }else{
                            $old_lock_time = strtotime($res['lock_time']);
                            $new_time = time();
                            $result = [
                                'lock_time' =>$old_lock_time,
                                'new_time' =>$new_time,
                            ];
                            $this -> returnCode = 200;
                            $this -> returnMsg="时间未过期";
                            $this -> returnData = $result;
                        }
                    }else{//如果不是今天，重新计时
                        $lock_time = date("Y-m-d H:i:s", time());
                        $data = [
                            'lock_time'=>$lock_time,
                        ];
                        $result = D('Monitoring')->updateDate($user_id,$data);
                        if($result){
                            $old_lock_time = strtotime($res['lock_time']);
                            $new_time = time();
                            $result = [
                                'lock_time' =>$old_lock_time,
                                'new_time' =>$new_time,
                            ];
                            $this -> returnCode = 200;
                            $this -> returnMsg="用户最后一次登录时间不是今天，修改锁定时间成功";
                            $this -> returnData = $result;
                        }else{
                            $this -> returnCode = 201;
                            $this -> returnMsg="用户最后一次登录时间不是今天，修改锁定时间失败";
                        }
                    }
                }else{
                    $this -> returnCode = 202;
                    $this -> returnMsg="此用户未开启青少年模式";
                }
            }else{
                $this -> returnCode = 202;
                $this -> returnMsg="用户没有开启过监控模式";
            }

        } catch (Exception $e) {
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 接收密码 修改上锁时间
     * 参数说明:请求方式为post
     * @param  string $token 用户ID
     * @param  int $$monitoring_pwd 用户密码
     * 返回值说明:
     */
    public function startTeenagers($token,$monitoring_pwd){
        $value = [
            'token'=>$token,
            'monitoring_pwd'=>md5($monitoring_pwd)
            ];
        try {
            $user_id = RedisCache::getInstance()->get($token);
            $where = [
                'user_id'=>$user_id,
                'monitoring_pwd'=>$value['monitoring_pwd']
            ];
            $res = D('Monitoring')->idFind($where);
            if($res){
                $lock_time = date("Y-m-d H:i:s", time());
                $data = [
                    'lock_time'=>$lock_time,
                ];
                $result = D('Monitoring')->updateDate($user_id,$data);
                if($result){
                    $this -> returnCode = 200;
                    $this -> returnData="延长锁定时间成功";
                }else{
                    $this -> returnCode = 201;
                    $this -> returnMsg="延长锁定时间失败";
                }
            }else{
                $this -> returnCode = 202;
                $this -> returnMsg="密码错误";
            }
        } catch (Exception $e) {
            $this -> returnCode = $e ->getCode();
           $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
    //     * 开启青少年 or 家長模式 禁用充值
    //     * @param string $token 用户的token参数
    //     */
    public function checkSatus($token) {
        $token = $_REQUEST['token'];
        try {
            $user_id = RedisCache::getInstance()->get($token);
            $where = ['user_id'=>$user_id];
            $res = D('Monitoring')->idFind($where);
//            var_dump($res);die;
            if($res['monitoring_status'] == 1){
                $this -> returnCode = 204;
                $this -> returnMsg="开启了青少年模式，无法进行充值行为";
            }else if($res['parents_status'] == 1){
                $this -> returnCode = 205;
                $this -> returnMsg="开启了家長模式，无法进行充值行为";
            }else{
                $this -> returnCode = 200;
                $this -> returnData="正常模式，可以进行充值";
            }
        } catch (Exception $e) {
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }



    /**
     * 家长模式  无法开启个人房间
     * 参数说明:请求方式为post
     * @param  string token  用户ID
     * 返回值说明:
     */
    public function accessRoom($token){
        $token =$_REQUEST['token'];
        try{
            $user_id = RedisCache::getInstance()->get($token);
            $where = ['user_id'=>$user_id];
            $res = D('Monitoring')->idFind($where);
            if($res['parents_status'] == 1){
                $this -> returnCode = 205;
                $this -> returnMsg="已开启家长模式，无法进入个人房间";
            }else if($res['parents_status'] == 0){
                $this -> returnCode = 200;
                $this -> returnData="未开启家长模式，可以进入房间";
            }
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }

    /**
     * 弹窗
     * 参数说明:请求方式为post
     * @param  string token  用户ID
     * 返回值说明:
     */
    public function checkPop($token){
        $token =$_REQUEST['token'];
        try{
            $user_id = RedisCache::getInstance()->get($token);
            $res = D('Member')->getOneById($user_id);
            if($res){
                $pop_time = $res['pop_time'];//用戶最后一次弹窗時間//普通
                $old_time = substr($pop_time,0,10); //轉換格式
                $constraint_time = $res['constraint_time'];
                $old_constraint = substr($constraint_time,0,10);//轉換格式
                $new_time = date('Y-m-d'); //今天時間
                $checkTime = strtotime(date("Y-m-d H:i:s", time()));
                $beginTime= strtotime($new_time.' '."06:00" . ":00");
                $endTime = strtotime(date($new_time.' '. "22:00" . ":00"));
                if($checkTime >= $beginTime && $checkTime <= $endTime) {//判断用户当前请求的时间是否6.00-22.00区间 //走普通
                    if ($old_time != $new_time) { //普通弹窗时间
                        $pop = date("Y-m-d H:i:s", time());
                        $data = ['pop_time' => $pop,];
                        $result = D('Member')->updateDate($user_id, $data);
                        if ($result) {
                            $this->returnCode = 202;
                            $this->returnData = "弹出防沉迷";
                        } else {
                            E('最后弹窗时间未存储成功');
                        }
                    }else{
                        $this -> returnCode = 201;
                        $this -> returnMsg="今日已弹出过";
                    }
                }else{
                    if($old_constraint != $new_time){
                        $constraint = date("Y-m-d H:i:s", time());
                        $data = ['constraint_time'=>$constraint];
                        $result = D('Member')->updateDate($user_id, $data);
                        if($result){
                            $this->returnCode = 202;
                            $this->returnData = "弹出防沉迷";
                        }else{
                            E('监控状态最后弹窗时间未存储成功');
                        }
                    }else{
                        $this -> returnCode = 201;
                        $this -> returnMsg="今日已弹出过";
                    }
                }
            }else{
                E('用户不存在');
            }
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
        }
    /**
     * 22.00-6.00 进入强制锁时间
     * 参数说明:请求方式为post
     * @param  string token  用户ID
     * 返回值说明:
     */
    public function checkConsLock($token){
        $token = $_REQUEST['token'];
        try{
            $user_id = RedisCache::getInstance()->get($token);
            $where = ['user_id'=>$user_id];
            $res = D('Monitoring')->idFind($where);
            if($res['monitoring_status'] == 1){
                $new_time = date('Y-m-d'); //今天時間
                $checkTime = strtotime(date('Y-m-d H:i:s',time()));
                $beginTime= strtotime(date($new_time.' '."06:00" . ":00"));
                $endTime = strtotime(date($new_time.' '. "22:00" . ":00"));
                if ($checkTime >= $beginTime && $checkTime <= $endTime) {
                    $backTime = strtotime(date('Y-m-d H:i:s',time()));
                    $resTime = strtotime(date($new_time.' '. "22:00" . ":00"));
                    $timeDifference = $resTime - $backTime;
                    $result = [
                        'timeDifference'=>$timeDifference,
                        'Msg'=>'未到22-6 区间时间'
                    ];
                    $this -> returnCode = 200;
                    $this -> returnData=$result;
                }else{
                    $this -> returnCode = 207;
                    $this -> returnMsg="青少年模式开启，22.00-6.00期间,强制上锁状态";
                }
            }else{
                $this -> returnCode = 200;
                $this -> returnData="未开启青少年模式";
            }
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
}

    