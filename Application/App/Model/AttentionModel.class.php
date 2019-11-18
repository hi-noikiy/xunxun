<?php

namespace Api\Model;
use Think\Model;

class AttentionModel extends Model{
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
   	public function getlist($where,$field='',$limit=''){
   	    return $this->where($where)->Field($field)->limit($limit)->select();
   	    //echo $this->_Sql();
   	}
   	/**统计关注 粉丝条数
   	 */
   	public function attentioncount($where){
   	    return $this->where($where) ->count();
   	}

    /**检测当前两用户是否关注
     * @param $uid  关注用户id
     * @param $touid    被关注的用户id
     * @return array
     */
    public function is_attention($uid,$touid){
        $where = [
            "userid" => $uid,
            "userided" => $touid,
        ];
        return $this->where($where) ->find();
    }
}
