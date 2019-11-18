<?php

namespace Api\Model;
use Think\Model;


class DebarModel extends Model{

    /**
     * 获取禁言(禁止)列表数据
     * @param $data
     * @return false|mixed
     */
    public function getList($data){
        $where['type'] = $data['type'];
        $where['room_id'] = $data['room_id'];
        return $this ->field("id as debar_id,user_id,room_id,type") ->where($where)-> select();
        // echo $this->_Sql(); 	//调试sql 语句 __RECOMMEND_CAR__
    }

    /**
     * 禁言禁入操作
     * @param $dataes
     */
    public function addData($dataes){
        $dataes["createtime"] = time();
        return $this ->add($dataes);
    }

    /**
     * 解除房间禁言(禁入)操作
     * @param $dataes
     * @return mixed
     */
    public function setDel($dataes){
        $where = [
            "user_id" => $dataes['user_id'],
            "room_id" => $dataes['room_id'],
            "type" => $dataes['type'],
        ];
        return $this->where($where) ->delete();
    }

    /**当前用户是否存在
     * @param $debardata
     */
    public function isDebar($debardata){
        $where = [
            "user_id" => $debardata['user_id'],
            "room_id" => $debardata['room_id'],
            "type" => $debardata['type'],
        ];
        return $this->where($where) ->find();
    }

    /**当前用户是否被禁言
     * @param $room_id
     * @param $user_id
     */
    public function isRoomSpeak($room_id,$user_id){
        $where = [
            "room_id" => $room_id,
            "user_id" => $user_id,
            "type" => 2,
        ];
        return $this->where($where) ->find();
    }
    /**统计当前房间禁言人数
     * @param $room_id
     * @return int|string
     */
    public function setCount($room_id){
        $where['room_id'] = $room_id;
        $where["type"] = 2;
        return $this -> where($where)->count();
    }





}
