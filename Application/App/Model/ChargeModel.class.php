<?php

namespace Api\Model;
use Think\Model;

class ChargeModel extends Model{
         /**
         * 关注
         */
   	public function addData($data){
          $this->add($data);
       // echo $this->_Sql();
   	}
   	/**
   	 * 取消关注
   	 */
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
   	
   	/**充值列表
   	 */
   	public function getChargeList(){
   	    $chargeInfo=$this->where('id>0')->field('rmb,diamond,present')->select();
   	    $data = array();
   	    if (!empty($chargeInfo)) {
   	        foreach ($chargeInfo as $k => $v) {
   	            $data[$v['rmb']] = $v;
   	        }
   	    }
   	    
   	    return $data;
   	}
   	   	/**
   	 * 充值列表
   	 */
   	public function getlists($field){
   	    return $this->where("id>0")->field($field)->select();
   	    //echo $this->_Sql();
   	}
}
