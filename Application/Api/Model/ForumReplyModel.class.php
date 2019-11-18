<?php

namespace Api\Model;
use Think\Model;

class ForumReplyModel extends Model{
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
   	    return $this->where($where)->Field($field)->limit($limit)->order($order)->select();
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
     $sql = "select count(id) as num,forum_id from zb_forum_reply where forum_id in ( ".$str." ) and reply_status=1 group by forum_id";
     $res = $this->query($sql);
     if (!empty($res)) {
     	$res = array_column($res, 'num','forum_id');
     }
     return $res;
   }

   //总条数
      public function getAllcountNum($forum_id=0)
      {
         if ($forum_id > 0) {
            $sql = "select count(id) as num from zb_forum_reply where forum_id = ".$forum_id." and reply_status = 1";
         }else{
            $sql = "select count(id) as num from zb_forum_reply where reply_status = 1";
         }
        
        $res = $this->query($sql);
        return $res[0]['num'];
      }

   	
}
