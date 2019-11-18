<?php

namespace Api\Model;
use Think\Model;

class ForumModel extends Model{
     /**
     * 添加
     */
   	public function addData($data){
         return $this->add($data);
       // echo $this->_Sql();
   	}

   	/**
   	 * 更新
   	 */
   	public function updateData($where,$data){
   	    return $this -> where($where)->save($data);
   	}

   	/**
   	 * 删除
   	 */
   	public function del($where){
   	    return $this -> where($where)->delete();
   	    //echo $this->_Sql();
   	}
   	
   	/**
   	 * 查询列表
   	 */
   	public function getlist($where,$field,$limit,$order='createtime desc'){
   		$res = array();
   		if ($where) {
   			$res = $this->where($where)->Field($field)->limit($limit)->order($order)->select();
   		}else{
   			$res = $this->Field($field)->limit($limit)->order($order)->select();
   		}
   	    return $res;
   	    //echo $this->_Sql();
   	}

   	/**
   	 * 查询单条
   	 */
   	public function getOne($where,$field){
   	    return $this->where($where)->Field($field)->find();
   	    //echo $this->_Sql();
   	}

   	/**统计
   	 */
   	public function countNum($where){
   	    return $this->where($where) ->count();
   	}

      //总条数
      public function getAllcountNum($uid=0)
      {
         if ($uid > 0) {
            $sql = "select count(id) as num from zb_forum where forum_uid = ".$uid." and forum_status = 1";
         }else{
            $sql = "select count(id) as num from zb_forum where forum_status = 1";
         }
        
        $res = $this->query($sql);
        return $res[0]['num'];
      }
   	
   

   	
}
