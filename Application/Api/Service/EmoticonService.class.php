<?php
namespace Api\Service;

use Api\Model\EmoticonModel;
use Think\Service;

class EmoticonService extends Service
{
    protected $_modelName = '\Api\Model\EmoticonModel';
    /**
     * @var DebarModel
     */
    protected $_model;

    /**获取表情列表
     * @param $data
     * @return mixed
     */
    public function getList($data){
        return $this -> _model -> getList($data);
    }


}