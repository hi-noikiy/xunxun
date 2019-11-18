<?php
namespace Api\Service;

use Api\Model\CoindetailModel;
use Think\Service;

class CoindetailService extends Service
{
    protected $_modelName = '\Api\Model\CoindetailModel';

    /**
     * @var AreaModel
     */
    protected $_model;

    /**获取该用户在某个对应房间消费爵位操作(统计不同房间的同一房间的数据)
     * @return mixed
     */
    public function getRoomduke($user_id){
        return $this -> _model -> getRoomduke($user_id);
    }

    /**获取用户在某个对应用户消费爵位操作(统计同一用户的数据)
     * @return mixed
     */
    public function getMemberduke($user_id){
        return $this -> _model -> getMemberduke($user_id);
    }

    /**获取用户在某个对应用户消费爵位等级名称操作(统计同一用户的数据)
     * @param uid 当前消费用户id
     * @param user_id   获取礼物用户id
     * @return mixed
     */
    public function getMembercoin($uid,$user_id){
        return $this -> _model -> getMembercoin($uid,$user_id);
    }



}