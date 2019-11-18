<?php

namespace Api\Model;
use Think\Model;

class VipModel extends Model{
    /**
     * 获取VIP数据
     */
    public function getList(){
        return $this ->field('id as ids,vip_rmb,vip_days,vip_content') -> select();
    }

    /**根据金额获取对应的vip数据
     * @param $grade_id
     * @return mixed
     */
    public function getOneByIdField($vip_rmb){
        $where['vip_rmb'] = $vip_rmb;
        return $this->where($where)->getField("vip_days");
    }
}