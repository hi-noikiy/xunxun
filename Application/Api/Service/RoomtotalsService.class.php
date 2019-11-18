<?php
namespace Api\Service;

use Api\Model\RoomtotalsModel;
use Think\Service;

class RoomtotalsService extends Service
{
    protected $_modelName = '\Api\Model\RoomtotalsModel';

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

    /**获取列表
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

    /**减少访问用户人数
     * @param $room_id
     * @return mixed
     */
    public function setDeces($room_id){
        return $this -> _model -> setDeces($room_id);
    }

    /**
     * 获取房间人气最高的10条数据
     * @param $limit
     * @return mixed
     */
    public function getRedList($limit){
        return $this -> _model -> getRedList($limit);
    }

    /**根据人气获取对应类型列表
     * @param $page
     * @param $room_type
     * @return mixed
     */
    public function getBytypeList($limit,$room_type){
        return $this->_model->getBytypeList($limit,$room_type);
    }

}