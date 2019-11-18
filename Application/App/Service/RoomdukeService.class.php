<?php
namespace Api\Service;

use Api\Model\RoomDukeModel;
use Think\Service;

class RoomDukeService extends Service
{
    protected $_modelName = '\Api\Model\RoomDukeModel';
    /**
     * @var DebarModel
     */
    protected $_model;

    /**获取该用户在某个对应房间消费爵位操作
     * @return mixed
     */
    public function getRoomduke($user_id){
        return $this -> _model -> getRoomduke($user_id);
    }

}