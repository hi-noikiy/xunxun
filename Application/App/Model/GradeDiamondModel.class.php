<?php

namespace Api\Model;
use Think\Model;

class GradeDiamondModel extends Model{
    /**
     * 获取特级虚拟币数据
     */
   	public function getList(){
        return $this ->field('id as grade_id,diamond_needed') -> select();
   	}

    /**根据等级获取对应的虚拟币数据
     * @param $grade_id
     * @param $getfield
     * @return mixed
     */
    public function getOneByIdField($grade_id,$getfield){
        $where['id'] = $grade_id;
        $field = $getfield;
        return $this->where($where)->getField($field);
    }

}
