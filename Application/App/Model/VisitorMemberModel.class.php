<?php

namespace Api\Model;
use Think\Model;


class VisitorMemberModel extends Model{

    /**获取当前访客用户是否存在
     * @param $uid
     * @param $touid
     * @return array
     */
    public function getFind($uid,$touid){
        $where = [
            "uid" => $uid,
            "touid" => $touid,
        ];
        return $this->where($where) ->find();
//        echo $this->_Sql();die();
    }

    /**修改当前用户访客时间
     * @param $uid
     * @param $touid
     */
    public function updateTime($uid,$touid){
        $where = [
            "uid" => $uid,
            "touid" => $touid,
        ];
        $update['ctime'] = time();
        return $this->where($where) ->save($update);
//        echo $this->_Sql();die();
    }

    /**修改当前用户访客所有状态
     * @param $uid
     * @param $touid
     */
    public function updateStatus($where){
        $update['status'] = 2;
        return $this->where($where) ->save($update);
//        echo $this->_Sql();die();
    }

    /**统计房间id查询对应的房间在线用户
     * @param $room_id
     * @return false|mixed
     */
    public function countes($where){
        return $this->where($where) ->count();
//        echo $this->_Sql();die();
    }

    /**查看访客记录列表
     * @param $room_id
     * @return false|mixed
     */
    public function getList($touid,$limit){
        $where = [
            "touid" => $touid,
        ];
        $fields = "vm.uid as user_id,m.nickname,m.avatar,m.intro,m.sex,m.totalcoin,vm.ctime";
        return $this->field($fields)->alias('vm')
            ->join("left join __MEMBER__ m on m.id=vm.uid")
            ->where($where)->order("vm.ctime desc")->limit($limit) ->select();
//        echo $this->_Sql();die();
    }

    /**查看访客记录(已读)
     * @param $user_id
     * @return int|string
     */
    public function oldcount($user_id){
        $where = [
            "touid" => $user_id,
            "status" => 2,
        ];
        return $this->where($where) ->count();
    }

    /**查看访客记录(未读)
     * @param $user_id
     * @return int|string
     */
    public function Newcount($user_id){
        $where = [
            "touid" => $user_id,
            "status" => 1,
        ];
        return $this->where($where) ->count();
    }



}
