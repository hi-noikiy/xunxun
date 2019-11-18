<?php

namespace Api\Model;
use Think\Model;

class ChargedetailModel extends Model{
         /**
         * 创建订单
         */
    public function addData($data){
        //var_dump($data);die;
      return  $this->add($data);
      // echo $this->_Sql();
   	}
   	//更新数据表
   	public function updateOrderStatus($data,$where){
   	    return $this -> where($where)->save($data);
   	    //echo $this->_Sql();
   	}
   	//删除操作
   	public function del($where){
   	    return $this -> where($where)->delete();
   	    //echo $this->_Sql();
   	}
   	
   	/**
   	 * 关注列表
   	 */
   	public function getlist($where,$field,$limit){
   	    return $this->where($where)->Field($field)->limit($limit)->select();
   	    //echo $this->_Sql();
   	}
   	/**统计关注 粉丝条数
   	 */
   	public function attentioncount($where){
   	    return $this->where($where) ->count();
   	}
   	
   	/**订单号校验
   	 */
   	public function getorder($where){
   	  //var_dump($where);die;
   	    return $this->where($where)->find();
   	}
}
