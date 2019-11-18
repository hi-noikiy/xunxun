<?php
namespace Api\Service;

use Api\Model\RoomManagerModel;
use Think\Service;

class RoomManagerService extends Service
{
    protected $_modelName = '\Api\Model\RoomManagerModel';

    /**
     * @var DebarModel
     */
    protected $_model;

    /**获取管理员列表
     * @param $data
     * @return mixed
     */
    public function getList($data){
        return $this -> _model -> getList($data);
    }

    /**添加数据操作
     * @param $dataes
     * @return mixed
     */
    public function addData($dataes){
        return $this -> _model -> addData($dataes);
    }

    /**获取管理员详情
     * @param $id
     */
    public function detail($id){
        return $this -> _model -> detail($id);
    }

    /**统计当前房间内的管理员数据(不能超过20个人)
     * @param $room_id
     * @return mixed
     */
    public function setCount($room_id){
        return $this -> _model -> setCount($room_id);
    }

    /**取消当前管理员
     * @param $data
     */
    public function removeManager($data){
        return $this -> _model -> removeManager($data);
    }

    /**获取当前房间的管理员
     * @param $data
     * @return mixed
     */
    public function isManager($data){
        return $this -> _model -> isManager($data);
    }

    /**判断当前用户是否为房间管理员
     * @param $room_id
     * @param $user_id
     */
    public function isRoomManager($room_id,$user_id){
        return $this -> _model -> isRoomManager($room_id,$user_id);
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