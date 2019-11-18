<?php

namespace Api\Model;
use Think\Model;

class EarncashModel extends Model{
         /**
         * 添加提现流水
         */
   	public function addData($data){
       return   $this->add($data);
       // echo $this->_Sql();
   	}
   	   	public function geterancashlist($where,$field,$limit="",$order=""){
   	    return  $this->where($where)->field($field)->order($order)->limit($limit)->select();
   	    // echo $this->_Sql();
   	}
   	public function earncount($where){
   	    return $this->where($where) ->count();
   	}

}
