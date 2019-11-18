<?php

namespace Api\Model;
use Think\Model;

class GuardValuesModel extends Model{

    /**
     * 获取用户守护房间数据
     * @param $user_id
     * @return false
     */
   	public function getGuardRoom($user_id){
        $where = [
            "user_id" => $user_id,
            "gv.type" => 0,     //房间类型
        ];
        $field = "gv.target_id as room_id,gv.guard_level,gv.creat_time,gv.long_day,g.image";
        return $this->field($field)->alias('gv')
            ->join("left join __GUARD__ g on g.id=gv.guard_level")
            ->where($where)->order('creat_time desc')->select();
//        echo $this->_Sql();die();
   	}

    /**获取用户守护个人房间数据
     * @param $user_id
     * @return false
     */
    public function getGuardMember($user_id){
        $where = [
            "user_id" => $user_id,
            "gv.type" => 1,     //个人类型
        ];
        $field = "gv.target_id as user_id,gv.guard_level,gv.creat_time,gv.long_day,g.image";
        return $this->field($field)->alias('gv')
            ->join("left join __GUARD__ g on g.id=gv.guard_level")
            ->where($where)->order('creat_time desc')->select();
//        echo $this->_Sql();die();
    }
    /**根据用户获取所有守护数据(包括个人与房间守护)
     * @param $user_id
     * @return false|mixed
     */
    public function getList($user_id){
        $where = [
            "user_id" => $user_id,
        ];
        $fields = "id as guard_values_id,target_id,type,guard_level,creat_time,long_day";
        return $this ->field($fields)->where($where) ->order("long_day desc")->select();
//        echo $this->_Sql();die();
    }

    /**根据守护获取对应的虚拟币数据
     * @param $grade_id
     * @param $getfield
     * @return mixed
     */
    public function getOneByIdField($duke_id,$getfield){
        $where['id'] = $duke_id;
        $field = $getfield;
        return $this->where($where)->getField($field);
    }

}
