<?php

namespace Api\Model;
use Think\Model;

class EmoticonModel extends Model{
    /**
     * 获取表情相关列表数据
     */
   	public function getList($data){
//   	    $where['type'] = $data;
        $where['type'] = array('in',"1,2");
        $field = "id as face_id,face_name,face_image,type,is_lock,animation,game_image";
        return $this ->field($field)->where($where)->order('is_sort desc') -> select();
//         echo $this->_Sql(); die();
   	}
}
