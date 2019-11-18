<?php
namespace Api\Service;

use Api\Model\MemberAvatarModel;
use Think\Service;

class MonitoringService extends Service
{
    protected $_modelName = '\Api\Model\MonitoringModel';
    /**
     * @var DebarModel
     */
    protected $_model;
    /**
     * 查询一条信息
     */
    public function idFind($where){
        return $this-> _model ->idFind($where);
    }
    /**
     * 修改密码
     */
    public function updatepwd($where,$password){
        return $this -> _model -> updatepwd($where,$password);
    }
    /**
     * 添加操作
     */
     public function addData($data){
        return $this -> _model -> addData($data);
    }
    /**
     * 更新操作
     */
    public function updateDate($id,$data){
        return $this -> _model -> updateDate($id,$data);
    }

    /**
     * 获取用户监控状态
     */
    public function userMonitoring($user_id){
        return $this -> _model -> userMonitoring($user_id);
    }
}