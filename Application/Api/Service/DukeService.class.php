<?php
namespace Api\Service;

use Api\Model\DukeModel;
use Think\Service;


class DukeService extends Service
{

    protected $_modelName = '\Api\Model\DukeModel';
    /**
     * @var ApplyModel
     */
    protected $_model;


    /**获取爵位中某个字段值
     * @param $id
     * @param $getfield
     * @return mixed
     */
    public function getOneByIdField($duke_id,$getfield){
        return $this -> _model -> getOneByIdField($duke_id,$getfield);
    }



}