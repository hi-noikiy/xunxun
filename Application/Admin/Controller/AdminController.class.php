<?php
namespace Admin\Controller;
use Admin\Service\AdminService;
use Think\Controller;
use Think\Exception;
use Think\Log;

class AdminController extends BaseController{

    /**后台登录接口
     * @param $username     用户名
     * @param $password     密码
     * @param $describe     账号与密码为空 code为 200001
     */
    public function login(){
        //接口值
        $data = [
            "username" => I("post.username"),
            "password" => I("post.password"),
        ];
        try{
            //校验数据
            if(empty($data['username']) && empty($data['passwrod'])){
                E("账号或密码不能为空",200001);
            }
            //md5加密操作
            //登录操作
            $login_reusltes = AdminService::getInstance() ->loginAdmin($data['username'],$data['password']);
            if($login_reusltes){
                $token = generateToken(C('SALT'));
                //更新token值
                $token_resultes = AdminService::getInstance() ->updateById($login_reusltes['id'],$token);
                if(empty($token_resultes)){
                    E("该登录账号存在异常",200003);
                }else{
                    $_SESSION["token"] = $token;
                    $login_reuslt = D("admin")->getOneById($login_reusltes['id']);
                }
            }else{
                E("该登录账号不存在",200002);
            }
            //返回结果值
            $result = [
                "login_info"=> $login_reuslt,
            ];
            $this -> returnCode = 200;
            $this -> returnData = $result;
        }catch (\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();
    }

    /**
     * 修改密码功能接口
     * @param　string $token token值
     * @param  string $oldpwd oldpwd旧密码
     * @param string $newpwd newpwd新密码
     * @param string $restpwd restpwd确定密码
     */
    public function restpwd(){
        $data = [
            "token" => I("post.token"),
            "oldpwd" => I('post.oldpwd'),
            "newpwd" => I("post.newpwd"),
            "restpwd" => I("post.restpwd"),
        ];
        try{
            //0.校验密码规则(不能输入汉字和不能小于6位)
//            $checkpwd = "/^[0-9a-zA-Z]{6}$/";
//             $checkpwd = "/^[\x7f-\xff]+$/";
//            if(preg_match($checkpwd,$data['newpwd'])){
//                var_dump(123);die();
//                E("该密码不能小于六位且不能输入汉字",200006);
//            }
            if(preg_match('/[\x{4e00}-\x{9fa5}]/u', $data['newpwd'])){
                E("该密码不能输入汉字",200009);
            }
            if(empty($data['oldpwd']) || empty($data['newpwd']) || empty($data['restpwd'])){
                E("该参数不能为空",200008);
            }
            if(strlen($data['newpwd']) < 6){
                E("该密码不能小于六位",200007);
            }
            //1.检测原密码是否存在及正确
            $password = M("admin")->where(array("admin_token"=>$data['token']))->getField("password");
            if($password !== $data['oldpwd']){
                E("请输入正确原始的密码",200004);
            }
            //2.检测原密码与新密码是否为不一致
            if($password == $data['newpwd']){
                E("新密码与旧密码相同",200006);
            }
            //3.检验新密码与重置密码一致性(是否相同)
            if($data['newpwd'] !== $data['restpwd']){
                E("新密码与旧密码不一致",200005);
            }
            //修改登录数据密码
            $update['password'] = $data['newpwd'];
            $update_result = M("admin")->where(array("admin_token"=>$data['token']))->save($update);
//            echo M("admin")->getLastSql();die();
            $this -> returnCode = 200;
        }catch (\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();
    }

    /**
     * 退出后台接口
     */
    public function outlogin(){
        try{
            //清除session与token值
            $_SESSION['token'] = array();
            session_destroy();
            $this -> returnCode = 200;
        }catch (\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();

    }


}
