<?php
namespace Api\Service;

use Api\Model\RoomMemberModel;
use Think\Service;

class RoomMemberService extends Service
{
    protected $_modelName = '\Api\Model\RoomMemberModel';

    /**
     * @var DebarModel
     */
    protected $_model;

    /**
     * 根据id查找数据
     */
    public function getOneByIdField($id,$getfield){
        return $this -> _model -> getOneByIdField($id,$getfield);
    }

    /**获取该房间内在线用户列表
     * @param $data
     * @return mixed
     */
    public function getList($data){
        return $this -> _model -> getList($data);
    }

    /**添加数据操作
     * @param $data
     * @return mixed
     */
    public function addData($data){
        return $this -> _model -> addData($data);
    }

    /**
     * 删除在线用户的数据
     * @param $isDelete
     */
    public function setDelete($isDelete){
        return $this -> _model -> setDelete($isDelete);
    }
    /**统计房间内在线用户数
     * @param $room_id
     * @return mixed
     */
    public function countes($room_id){
        return $this->_model->countes($room_id);
    }

    /**根据用户获取对应的房间名称
     * @param $user_id
     * @return mixed
     */
    public function findRoom($user_id){
        return $this->_model->findRoom($user_id);
    }

    /**我管理的房间列表接口(房间管理员列表)
     * @param $user_id  用户id
     * @return mixed
     */
    public function getMyRoom($user_id){
        return $this->_model->getMyRoom($user_id);
    }

}