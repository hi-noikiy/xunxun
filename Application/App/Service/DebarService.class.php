<?php
namespace Api\Service;

use Api\Model\DebarModel;
use Think\Service;

class DebarService extends Service
{
    protected $_modelName = '\Api\Model\DebarModel';

    /**
     * @var DebarModel
     */
    protected $_model;

    /**
     * 根据id查找数据
     */
    public function findOneById($id){
        return $this -> _model -> findOneByIde($id);
    }

    /**获取禁言(禁止)列表
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

    /**解除房间禁言(禁入)操作
     * @param $dataes
     * @return mixed
     */
    public function setDel($dataes){
        return $this -> _model -> setDel($dataes);
    }

    /**是否被禁入操作
     * @param $debardata
     * @return mixed
     */
    public function isDebar($debardata){
        return $this -> _model -> isDebar($debardata);
    }

    /**当前用户是否被禁言
     * @param $room_id
     * @param $user_id
     */
    public function isRoomSpeak($room_id,$user_id){
        return $this -> _model -> isRoomSpeak($room_id,$user_id);
    }
    /**统计当前房间内的禁言人数数据
     * @param $room_id
     * @return mixed
     */
    public function setCount($room_id){
        return $this -> _model -> setCount($room_id);
    }

}