<?php

namespace Api\Model;
use Think\Model;

class GiftModel extends Model{
    /**
     * 获取礼物列表数据
     */
   	public function getList($data){
   	    $where['bigtypes'] = $data;
        $where['status'] = 1;
        $field = "id as gift_id,gift_name,gift_number,gift_coin,gift_image,gift_type,gift_animation,animation,class_type,broadcast";
        return $this ->field($field)->where($where) ->order('gift_coin asc')-> select();
//        return $this ->field($field)->where($where) -> select();
//         echo $this->_Sql();
   	}
   	
   	    public function findOneById($id){
        $where['id']  = $id;
        return $this -> where($where)->find();
//        echo $this->_Sql();die();
    }
}
