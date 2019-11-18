<?php
namespace Api\Service;

use Api\Model\ComplaintsModel;
use Think\Service;

class ComplaintsServer extends Service
{
    protected $_modelName = '\Api\Model\ComplaintsModel';
    /**
     * @var DebarModel
     */
    protected $_model;

    /**获取当前用户当天举报所需用户的时间
     * @param $data
     * @return mixed
     */
    public function getFieldTime($data){
        return $this -> _model -> getFieldTime($data);
    }


}