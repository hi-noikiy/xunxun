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

    //查询列表
    public function getGiftList(){
        return $this -> _model -> getListOrder();
    }

    /**添加数据操作
     * @param $dataes
     * @return mixed
     */
    public function addData($dataes){
        return $this -> _model -> addData($dataes);
    }

    /**获取所有礼物列表(包括上下架礼物)
     * @return mixed
     */
    public function getListAll(){
        return $this -> _model ->getListAll();
    }

}