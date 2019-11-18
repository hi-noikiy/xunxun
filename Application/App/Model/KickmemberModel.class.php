<?php

namespace Api\Model;
use Think\Model;

class KickMemberModel extends Model{
        //增加举报信息
   	public function addData($data){
   	      $this -> add($data);
   	  echo $this->_Sql();die();
   	}
   	//获取信息是否举报过
   	public function getbymsg($where,$field){
   	    
   	 return $this->where($where)->getField($field);
   	    //  echo $this->_Sql();die();
   	}
   	//修改用户被踢次数
   	public function updateData($where,$data){
   	    
   	    return $this->where($where)->save($data);
   	    //  echo $this->_Sql();die();
   	}
}