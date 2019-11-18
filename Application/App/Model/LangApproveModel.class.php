<?php

namespace Api\Model;
use Think\Model;

class LangApproveModel extends Model{
    /**
     * 添加操作功能
     */
    public function addData($data){
        return $this ->add($data);
//        echo $this->_Sql();die();
    }

    /**当前语言认证用户
     * @param $user_id
     */
    public function approveinfo($user_id){
        $where["user_id"] = $user_id;
        return $this ->where($where)->order("uptime desc")->find();
    }
}
