<?php

namespace Api\Model;
use Think\Model;

class RoomDukeModel extends Model{

    /**根据房间获取对应的爵位等级数据
     * @param $grade_id
     * @param $getfield
     * @return mixed
     */
    public function getOneByIdField($duke_id,$getfield){
        $where['room_id'] = $duke_id;
        $field = $getfield;
        return $this->where($where)->getField($field);
    }
    /**根据房间id和 用户id获取爵位等级等级数据
     * @param $grade_id
     * @param $getfield
     * @return mixed
     */
    public function getdukelv($where,$field){
        return $this->where($where)->getField($field);
    }

    /**根据当前用户获取所有消费房间及等级
     * @param $user_id  用户id
     */
    public function getRoomduke($user_id){
        $where['md.user_id'] = $user_id;
        $where['md.grade'] = array('egt',1);
        $field = "md.room_id,md.grade,md.duke_coins,lm.room_name";
        return $this ->field($field) ->alias("md")
            ->join("left join __LANGUAGEROOM__ lm on lm.id=md.room_id")
            ->where($where)-> select();
//        echo $this->_Sql();die();
    }
}
