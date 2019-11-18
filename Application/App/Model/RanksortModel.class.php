<?php

namespace Api\Model;
use Think\Model;

class RanksortModel extends Model{
    /**
     * 添加缓存数据
     */
   	public function addData($data){
   	    //var_dump($data);die;
        return $this->add($data);
        // echo $this->_Sql();
   	}
   	
   	/**
   	 * 删除过期数据
   	 */
   	public function delData($where){
   	    //var_dump($where);die;
   	    return $this->where($where)->delete();
   	    // echo $this->_Sql();
   	}
   	
   	/**
   	 * 获取排行榜列表
   	 */
   	public function rankList($where,$field,$limit){
   	    //var_dump($where);die;
   	    return $this->where($where)->field($field)->limit($limit)->select();
   	    // echo $this->_Sql();
   	}

    /**
     * 获取排行榜列表
     */
    public function rankListes($where,$field,$limit){
        //var_dump($where);die;
        return $this->where($where)->field($field)->limit($limit)->select();
       /* return $this ->field($field)->where($where)->group('roomid') ->having('coin>=1000') -> select();*/
        // echo $this->_Sql();
    }

}
