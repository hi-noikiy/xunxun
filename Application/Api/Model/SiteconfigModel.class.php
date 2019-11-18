<?php

namespace Api\Model;
use Think\Model;


class SiteconfigModel extends Model{


    /**根据id获取对应某一个字段
     * @param $id
     * @param $getfield
     * @return mixed
     */
    public function getOneByIdField($field){
        $where=array('id>0');
        return $this->where($where)->getField($field);
//        echo $this->_Sql();die();
    }
}
