<?php

namespace Api\Model;
use Think\Model;

class GuardModel extends Model{

    /**
     * 获取守护配置数据
     * @param $guard_id 守护配置id
     * @return false
     */
   	public function getGuardfind($guard_id){
        $where = [
            "id" => $guard_id,
        ];
        $field = "id as guard_id,image,nickname_logo,is_texiao,is_room_out,is_gift";
        return $this->field($field)->where($where)->find();
//        echo $this->_Sql();die();
   	}

    /**
     * 获取所有守护配置数据
     * @param $type type 0 房间 1个人守护
     * @return false
     */
    public function getGuardlist($type){
        $where = [
            "type" => $type,
        ];
        $field = "id as guard_id,long_time,coin,image,nickname_logo,is_texiao,is_room_out,is_gift,type,gold_marks";
        return $this->field($field)->where($where)->select();
//        echo $this->_Sql();die();
    }

    /**根据守护id获取对应的守护数据
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
