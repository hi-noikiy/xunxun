<?php

namespace Api\Model;
use Think\Model;

class ComplaintsModel extends Model{
    /**
     * 获取当前用户举报时间数据
     */
   	public function getFieldTime($data){
        $where = [
            "user_id" => $data['user_id'],
            "to_uid" => $data['to_uid'],
        ];
        return $this ->where($where)->order("create_time desc") -> getField("create_time");
//         echo $this->_Sql(); die();
   	}
}
