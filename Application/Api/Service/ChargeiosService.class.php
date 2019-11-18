<?php
namespace Api\Service;

use Api\Model\ChargeiosModel;
use Think\Service;

class ChargeiosService extends Service
{
    protected $_modelName = '\Api\Model\ChargeiosModel';
    /**
     * @var DebarModel
     */
    protected $_model;
    /**获取充值列表
     * @param $data
     * @return mixed
     */
    public function getlists(){
        return $this -> _model -> getlists();
    }


}