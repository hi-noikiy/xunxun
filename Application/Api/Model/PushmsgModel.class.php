<?php

namespace Api\Model;
use Think\Model;

class PushmsgModel extends Model{
    /**
     * 获取用户投诉列表
     */
   	public function addData($data){
        return $this->add($data);
        // echo $this->_Sql();
   	}

}
