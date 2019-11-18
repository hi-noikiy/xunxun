<?php
namespace Api\Service;

use Api\Model\LanguageroomModel;
use Think\Service;

class LanguageroomService extends Service
{
    protected $_modelName = '\Api\Model\LanguageroomModel';

    /**
     * @var BusinessModel
     */
    protected $_model;

    /**
     * 添加操作
     */
    public function addData($data){
        return $this -> _model -> addData($data);
    }

    /**
     * 根据用户获取房间信息
     * @param $data
     */
    public function getFind($data){
        return $this->_model->getFind($data);
    }
    /*
     * 根据房间id获取详细信息
     */
    public function getDeatil($room_id){
        return $this->_model->getDeatil($room_id);
    }

    /**统计对应类型的房间总数
     * @param $where
     * @return mixed
     */
    public function countes($room_type){
        return $this->_model->countes($room_type);
    }

    /**修改对应用户加锁操作
     * @param $room_id
     * @return mixed
     */
    public function getByroomidUpdate($room_id,$room_lock){
        return $this->_model->getByroomidUpdate($room_id,$room_lock);
    }

    /**修改当前房间的标签属性
     * @param $room_id
     * @param $room_tags
     * @return mixed
     */
    public function getUpdateTags($room_id,$room_tags){
        return $this->_model->getUpdateTags($room_id,$room_tags);
    }

    /**房间信息修改
     * @param $room_id
     * @param $result_keys
     * @param $result_values
     */
    public function getUpdate($room_id,$result_keys,$result_values){
        return $this->_model->getUpdate($room_id,$result_keys,$result_values);
    }
    /**首页搜索房间条件
     * @param $condition
     */
    public function Search($search){
        return $this -> _model -> Search($search);
    }

    /**根据房间Id获取用户的id
     * @param $room_id
     * @return mixed
     */
    public function getUserField($room_id){
        return $this->_model->getUserField($room_id);
    }
    /**人气值热门排序
     * @param $limit
     * @return mixed
     */
    public function getRedList($limit){
        return $this->_model->getRedList($limit);
    }

    /**根据分类人气值排序
     * @param $limit
     * @return mixed
     */
    public function getBytypeList($limit,$room_type){
        return $this->_model->getBytypeList($limit,$room_type);
    }

    /**我的房间列表接口
     * @param $user_id  用户id
     * @return mixed
     */
    public function getMyRoom($user_id){
        return $this->_model->getMyRoom($user_id);
    }

    /**
     * 根据id查找数据
     */
    public function getOneByIdField($id,$getfield){
        return $this -> _model -> getOneByIdField($id,$getfield);
    }
}