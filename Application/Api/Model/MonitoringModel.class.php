<?php

namespace Api\Model;

use Think\Model;


class MonitoringModel extends Model{
    /**
     * 查询一条数据
     * @param $id
     * @return bool|false|int
     */
    public function idFind($where){
        return $this->where($where)->find();
    }

    /**
     * 修改密码
     * @param $id
     * @return bool|false|int
     */
    public function updatepwd($where,$data){
        return $this -> where($where) -> save($data);
//        echo $this->_sql();die;
    }
    /**
     * 添加操作
     * @return bool|false|int
     */
    public function addData($data){
         return $this -> add( $data );
//      echo $this->_sql();die;

   }

   public function selectAll(){
         return $this -> select();
//      echo $this->_sql();die;

   }
     /**
     * 更新操作
     * @param $id
     * @return bool|false|int
     */
    public function updateDate($user_id,$data){
        $where = [
            'user_id' => $user_id,
        ];
        return $this -> where($where) ->save($data);
    }
    /**
     * 获取用户监控状态
     * @param $id
     * @return array|false
     */
    public function userMonitoring($user_id){
        $where = [
            "user_id"=>$user_id,
        ];
        $field = "user_id as user_id,monitoring_status,parents_status";
        return $this->field($field)->where($where)->find();
//        echo $this->_Sql();die;
    }


}