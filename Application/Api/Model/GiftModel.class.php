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
        return  $this ->field($field)->where($where) ->order('is_sort desc,gift_coin asc')-> select();
//         echo $this->_Sql();
    }

    //礼物列表
    public function getListOrder(){
        // $where['bigtypes'] = $data;
        // $where['status'] = 1;
        $field = "id as gift_id,gift_name,gift_number,gift_coin,gift_image,gift_type,gift_animation,animation,class_type,broadcast";
        return  $this ->field($field)->where($where) ->order('gift_coin desc')-> select();
//         echo $this->_Sql();
    }

    /**根据礼物id获取礼物详情
     * @param $id
     * @return mixed
     */
    public function findOneById($id){
        $where['id']  = $id;
        return $this -> where($where)->find();
//        echo $this->_Sql();die();
    }

    /**根据礼物id获取对应的数据值(礼物详情)
     * @param $id
     * @return mixed
     */
    public function getByidDetail($id){
        $where['id'] = $id;
        $field = 'id as gift_id,gift_name,gift_image,gift_animation,animation,class_type';
        return $this ->field($field) -> where($where)->find();
//        echo $this->_Sql();die();
    }
    /**
     * 砸金蛋礼物列表
     */
    public function getlistgift($type){
        $where['status'] = 1;
        if($type == 1){
            $where['color_weight'] = array('gt',0);
        }else{
            $where['one_weight'] = array('gt',0);
        }
        $field = "id as gift_id,gift_name,gift_coin,gift_image,prize_rate";
        return $this ->field($field)->where($where) ->order('gift_coin desc')-> select();
//        echo $this->_Sql();die();
    }

    /**获取所有礼物(包括上下架的礼物)
     * @return mixed
     */
    public function getListAll(){
        $field = "id as gift_id,gift_name,gift_number,gift_coin,gift_image,gift_type,gift_animation,animation,class_type,broadcast,status";
        return  $this ->field($field) ->order('is_sort desc,gift_coin asc')-> select();
    }

    /**
     * 砸金蛋与砸彩蛋礼物列表
     */
    public function getlistgiftegg(){
        $where['status'] = 1;
        $field = "id as gift_id,gift_name,gift_coin,gift_image,one_weight,color_weight";
        return $this ->field($field)->where($where) ->order('gift_coin desc')-> select();
    }
}
