<?php
namespace Admin\Service;
use Aadmin\Model\AdminModel;
use Think\Service;


class AdminService extends Service
{

    protected $_modelName = '\Admin\Model\AdminModel';
    /**
     * @var ApplyModel
     */
    protected $_model;

    /**账户登录
     * @param $username     用户名
     * @param $password     密码
     * @return mixed        返回值
     */
    public function loginAdmin($username,$password){
        return $this -> _model -> loginAdmin($username,$password);
    }

    /**修改token值
     * @param $id   id唯一值
     * @param $field    修改的字段值
     */
    public function updateById($id,$field){
        return $this-> _model -> updateById($id,$field);
    }
    /*
     * 修改密码
     * */
    public function updatepw($where,$password){
        return $this -> _model -> updatepw($where,$password);
    }


}