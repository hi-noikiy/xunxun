<?php

namespace Api\Model;
use Think\Model;

class DukeModel extends Model{
    /**
     * 获取爵位数据
     */
   	public function getList(){
        return $this ->field('id as duke_id,duke_coin') -> select();
   	}

    /**根据等级获取对应的虚拟币数据
     * @param $grade_id
     * @param $getfield
     * @return mixed
     */
    public function getOneByIdField($duke_id,$getfield){
        $where['id'] = $duke_id;
        $field = $getfield;
        return $this->where($where)->getField($field);
    }

    /**获取对应的爵位等级数据
     * @param $duke_id
     * @return array|false
     */
    public function getFind($duke_id){
        $where = [
            "id" => $duke_id,
        ];
        return $this->where($where)->find();
    }

}
