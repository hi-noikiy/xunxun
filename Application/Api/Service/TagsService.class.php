<?php
namespace Api\Service;

use Api\Model\TagsModel;
use Think\Service;

class TagsService extends Service
{
    protected $_modelName = '\Api\Model\TagsModel';

    /**
     * @var DebarModel
     */
    protected $_model;

    /**
     * 根据id查找数据
     */
    public function getList(){
        return $this -> _model -> getList();
    }
    /**根据id获取对应某一个字段
     * @param $id
     * @param $getfield
     * @return mixed
     */
    public function getOneByIdField($id,$getfield){
        return $this -> _model -> getOneByIdField($id,$getfield);
//        echo $this->_Sql();die();
    }


}