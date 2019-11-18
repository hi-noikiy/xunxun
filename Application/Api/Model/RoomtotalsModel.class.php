<?php

namespace Api\Model;
use Think\Model;


class RoomtotalsModel extends Model{

    /**获取房间内人气最高的10条数据
     * @param $data
     */
    public function getRedList($limit){
        $fields = "rt.rooms_id as room_id,rt.visitor_number,lr.room_name,lr.room_image,lr.room_type,lr.room_tags,lr.user_id";
        return $this->field($fields)->alias('rt')
            ->join("left join __LANGUAGEROOM__ lr on lr.id=rt.rooms_id")
            ->order('visitor_number desc')->limit($limit)->select();

//        echo $this->_Sql();die();
    }
    /**
     * 添加操作
     * @param $data
     * @return mixed
     */
    public function addData($data){
        return $this->add($data);
    }
    /**
     * 访问人数增加
     */
   	public function setInces($room_id){
        $where = [
            "rooms_id"=>$room_id,
            "stype"=>1,
        ];
        return $this->where($where)->setInc("visitor_number",1);
//          var_dump($this->_Sql());die();//调试sql 语句 __RECOMMEND_CAR__
   	}

    /**
     * 访问人数减少
     */
    public function setDeces($room_id){
        $where = [
            "rooms_id"=>$room_id,
            "stype"=>1,
        ];
         $this->where($where)->setDec("visitor_number",1);
//        var_dump($this->_Sql());die();//调试sql 语句 __RECOMMEND_CAR__
    }

    /**根据id获取对应某一个字段
     * @param $id
     * @param $getfield
     * @return mixed
     */
    public function getOneByIdField($id,$getfield){
        $where['rooms_id'] = $id;
        $field = $getfield;

        return $this->where($where)->getField($field);
//        echo $this->_Sql();die();
    }
    /**获取房间的类型人气倒叙N条数据
     * @param $data
     */
    public function getBytypeList($limit,$room_type){
        $where['room_types'] = $room_type;
        $fields = "rt.rooms_id,rt.visitor_number,lr.room_name,lr.room_image,lr.room_type,lr.room_tags,lr.user_id";
        return $this->field($fields)->alias('rt')
            ->join("left join __LANGUAGEROOM__ lr on lr.id=rt.rooms_id")
            ->where($where)->order('visitor_number desc')->limit($limit) -> select();

//        echo $this->_Sql();die();
    }



}
