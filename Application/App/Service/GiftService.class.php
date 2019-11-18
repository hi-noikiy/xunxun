<?php
namespace Api\Service;

use Api\Model\GiftModel;
use Think\Service;

class GiftService extends Service
{
    protected $_modelName = '\Api\Model\GiftModel';
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

    /**获取礼物列表
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

}