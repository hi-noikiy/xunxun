<?php

namespace Api\Model;
use Think\Model;


class RoomManagerModel extends Model{

    /**
     * 获取管理员列表数据
     * @param $data
     * @return false|mixed
     */
    public function getList($data){
        $where['rm.rooms_id'] = $data;
        $fields = "rm.rooms_id room_id,rm.user_id,m.avatar,m.nickname,m.pretty_id,m.pretty_avatar,m.pretty_avatar_svga";
        return $this ->field($fields) ->alias("rm")
            ->join("left join __MEMBER__ m on m.id=rm.user_id")
            ->where($where)-> select();
//         echo $this->_Sql(); 	die();//调试sql 语句 __RECOMMEND_CAR__
    }

    /**
     * 添加管理员操作
     * @param $dataes
     */
    public function addData($dataes){
        return $this ->add($dataes);
    }

    /**获取当前管理员信息
     * @param $id
     * @return array|false|mixed
     */
    public function detail($id){
        $field = "id as admin_id,rooms_id,user_id,creattime";
        $where = [
            "id" => $id,
        ];
        return $this->field($field)->where($where)->find();
    }

    /**统计当前房间管理员人数
     * @param $room_id
     * @return int|string
     */
    public function setCount($room_id){
        $where['rooms_id'] = $room_id;
        return $this -> where($where)->count();
    }

    /**取消当前房间管理人员
     * @param $data
     * @return int|string
     */
    public function removeManager($data){
        $where = [
            "rooms_id" => $data['room_id'],
            "user_id" => $data['user_id'],
        ];
        return $this -> where($where)->delete();
    }

    /**当前管理员是否存在
     * @param $data
     * @return array|false|mixed
     */
    public function isManager($data){
        $where = [
            "rooms_id" => $data['room_id'],
            "user_id" => $data['user_id'],
        ];
        return $this -> where($where)->find();
    }

    /**当前用户是否为当前房间的管理员
     * @param $room_id
     * @param $user_id
     * @return array|false|mixed
     */
    public function isRoomManager($room_id,$user_id){
        $where = [
            "rooms_id" => $room_id,
            "user_id" => $user_id,
        ];
        return $this -> where($where)->find();
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

    /**根据用户获取房间id
     * @param $user_id
     * @return mixed
     */
    public function findRoom($user_id){
        $where['user_id'] = $user_id;
        return $this ->where($where) ->getField("rooms_id");
    }





}
