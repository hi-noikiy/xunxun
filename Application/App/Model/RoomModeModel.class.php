<?php

namespace Api\Model;
use Think\Model;


class RoomModeModel extends Model{

    /**
     * 获取房间类型数据
     */
   	public function getList($status){
        $where = [
            "status" => $status,
            "mode_type" => 2,
        ];
        return $this ->where($where) ->field("id as mode_id,room_mode,mode_type as room_type") -> select();
//         echo $this->_Sql(); die();	//调试sql 语句 __RECOMMEND_CAR__
   	}

    /**根据id获取对应某一个字段
     * @param $id
     * @param $getfield
     * @return mixed
     */
    public function getOneByIdField($id,$getfield){
        $where['id'] = $id;
        $field = $getfield;
        return $this->where($where)->getField($field);
//        echo $this->_Sql();die();
    }

    /**
     * 获取首页房间类型数据(所有类型)
     */
    public function getListes(){
        return $this ->field("id as mode_id,room_mode,mode_type as room_type,status") -> select();
//         echo $this->_Sql(); die();	//调试sql 语句 __RECOMMEND_CAR__
    }



}
