<?php

namespace Api\Model;
use Think\Model;

class VipExpModel extends Model{

    /**
     * 获取等级经验值及其他配置数据
     * @param $guard_id 守护配置id
     * @return false
     */
   	public function detail($type){
        $where = [
            "type" => $type,
        ];
        $field = "id as vip_exp_id,type,exp_nuit,exp_values";
        return $this->field($field)->where($where)->find();
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
