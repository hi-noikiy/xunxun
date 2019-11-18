<?php
namespace Api\Service;

use Api\Model\MemberVipModel;
use Think\Service;

class MemberVipService extends Service
{
    protected $_modelName = '\Api\Model\MemberVipModel';

    /**
     * @var AreaModel
     */
    protected $_model;

    /**
     * @return mixed
     */
    public function getList(){
        return $this -> _model -> getList();
    }

}