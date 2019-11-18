<?php

namespace Api\Model;
use Think\Model;


class RoomMemberModel extends Model{

    /**
     * 添加在线用户新数据
     */
    public function addData($data){
        return $this ->add($data);
        // echo $this->_Sql(); 	//调试sql 语句 __RECOMMEND_CAR__
    }

    /**
     * 删除此房间的在线用户数据
     * @param $isDelete
     * @return int|mixed
     */
    public function setDelete($isDelete){
        $where = [
            "rooms_id" => $isDelete['rooms_id'],
            "user_id" => $isDelete['user_id'],
        ];
        return $this->where($where) ->delete();

    }

    /**统计房间id查询对应的房间在线用户
     * @param $room_id
     * @return false|mixed
     */
    public function countes($room_id){
        $where = [
            "rooms_id" => $room_id,
        ];
        return $this->where($where) ->count();
    }

    /**在线用户列表
     * @param $room_id
     * @return false|mixed
     */
    public function getList($room_id,$limit){
        $where = [
            "rooms_id" => $room_id,
        ];
        $fields = "rm.user_id,m.nickname,m.avatar,m.sex,m.is_vip";
        return $this->field($fields)->alias('rm')
            ->join("left join __MEMBER__ m on m.id=rm.user_id")
            ->where($where)->order("creattime desc")->limit($limit) ->select();
//        echo $this->_Sql();die();
    }

    /**根据用户获取房间id
     * @param $user_id
     * @return mixed
     */
    public function findRoom($user_id){
        $where['user_id'] = $user_id;
        $where['is_room'] = 1;
        return $this ->where($where) ->getField("rooms_id");
    }

    /**根据用户获取房间idd的详情信息
     * @param $user_id
     * @return mixed
     */
    public function findRooms($user_id,$field,$msg="",$limit=""){
        $where['user_id'] = $user_id;
        return $this ->where($where)->field($field)->order($msg)->limit($limit)->select();
        //echo $this->_Sql();die();
    }

    /**获取我管理的房间列表
     * @param $user_id
     * @return mixed
     */
    public function getMyRoom($user_id){
        $where['rm.user_id'] = $user_id;
        $field = "rm.rooms_id as room_id,lr.room_name,lr.room_type,lr.room_lock,lr.visitor_number,lr.is_live,rm.user_id";
        return $this->field($field)->alias('rm')
            ->join("left join __LANGUAGEROOM__ lr on lr.id=rm.rooms_id")
            ->where($where)->order('creattime desc')->select();
//        echo $this->_Sql();die();
    }

}
