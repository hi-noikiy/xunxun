<?php
namespace Api\Service;

use Api\Model\GuardValuesModel;
use Think\Service;


class GuardValuesService extends Service
{

    protected $_modelName = '\Api\Model\GuardValuesModel';
    /**
     * @var ApplyModel
     */
    protected $_model;

    /**获取当前用户守护房间数据
     * @param $user_id
     * @return mixed
     */
    public function getGuardRoom($user_id){
        return $this-> _model -> getGuardRoom($user_id);
    }

    /**获取当前用户守护个人数据
     * @param $user_id
     * @return mixed
     */
    public function getGuardMember($user_id){
        return $this-> _model -> getGuardMember($user_id);
    }

    /**获取当前用户守护所有的数据(守护与个人)
     * @param $user_id
     * @return mixed
     */
    public function getList($user_id){
        return $this-> _model -> getList($user_id);
    }

    /**获取爵位中某个字段值
     * @param $id
     * @param $getfield
     * @return mixed
     */
    public function getOneByIdField($duke_id,$getfield){
        return $this -> _model -> getOneByIdField($duke_id,$getfield);
    }



}