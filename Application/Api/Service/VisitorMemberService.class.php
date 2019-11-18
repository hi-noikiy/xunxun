<?php
namespace Api\Service;

use Api\Model\VisitorMemberModel;
use Think\Service;

class VisitorMemberService extends Service{

    protected $_modelName = '\Api\Model\VisitorMemberModel';

    /**
     * @var DebarModel
     */
    protected $_model;

    /**该用户最近访客是否存在
     * @param $uid
     * @param $touid
     * @return mixed
     */
    public function getFind($uid,$touid){
        return $this -> _model -> getFind($uid,$touid);
    }

    /**修改最近访客时间
     * @param $uid
     * @param $touid
     * @return mixed
     */
    public function updateTime($uid,$touid){
        return $this -> _model -> updateTime($uid,$touid);
    }

    /**获取最近访客历史数据列表
     * @param $touid
     * @return mixed
     */
    public function getList($touid,$limit){
        return $this -> _model -> getList($touid,$limit);
    }

    /**修改最近访客状态
     * @param $uid
     * @param $touid
     * @return mixed
     */
    public function updateStatus($where){
        return $this -> _model -> updateStatus($where);
    }

    /**添加数据操作
     * @param $data
     * @return mixed
     */
    public function addData($data){
        return $this -> _model -> addData($data);
    }

    /**统计该用户访客总人数
     * @param $room_id
     * @return mixed
     */
    public function countes($where){
        return $this->_model->countes($where);
    }

    /**最近访客查看数量(已查看的用户)
     * @param $where
     * @return mixed
     */
    public function oldcount($user_id){
        return $this->_model->oldcount($user_id);
    }

    /**最近访客查看数量(已查看的用户)
     * @param $where
     * @return mixed
     */
    public function Newcount($user_id){
        return $this->_model->Newcount($user_id);
    }

}