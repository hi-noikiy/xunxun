<?php

namespace Api\Model;
use Think\Model;

class ReportModel extends Model{
        //增加举报信息
   	public function addData($data){
   	    return $this -> add($data);
   	  //  echo $this->_Sql();die();
   	}
   	//获取信息是否举报过
   	public function getbymsg($where){
   	    
   	 return $this->where($where)->find();
   	    //  echo $this->_Sql();die();
   	}
}