<?php

namespace Api\Model;
use Think\Model;

class ForumEnjoyModel extends Model{
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
    public function getlistAll($where,$field){
        $res = $this->where($where)->Field($field)->select();
        $data = array();
        if (!empty($res)) {
          $data = array_column($res, 'enjoy_uid','forum_id');
        }
        return $data;
        //echo $this->_Sql();
    }
   	
   	/**
   	 * 查询列表
   	 */
   	public function getlist($where,$field,$limit){
   	    return $this->where($where)->Field($field)->limit($limit)->select();
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
   	
   public function getAllNum($str)
   {
     $sql = "select count(id) as num,forum_id from zb_forum_enjoy where forum_id in ( ".$str." ) group by forum_id";
     $res = $this->query($sql);
     if (!empty($res)) {
      $res = array_column($res, 'num','forum_id');
     }
     return $res;
   }

   	
}
