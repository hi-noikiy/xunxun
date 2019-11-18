<?php

namespace Api\Model;
use Think\Model;

class EmoticonModel extends Model{
    /**
     * 获取表情相关列表数据
     */
   	public function getList($data){
   	    $where['type'] = $data;
        $field = "id as face_id,face_name,face_image,is_lock,animation";
        return $this ->field($field)->where($where)->order('face_id desc') -> select();
//         echo $this->_Sql(); die();
   	}
}
