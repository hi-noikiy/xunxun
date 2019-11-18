<?php

namespace Api\Model;
use Think\Model;

class BindaplipayModel extends Model{
    /*添加绑定数据*/
    public function addData($data){
        return   $this->add($data);
        // echo $this->_Sql();
    }
         /**
         * 是否绑定
         */
    public function getbindmsg($where){
       return   $this->where($where)->select();
       // echo $this->_Sql();
   	}

}
