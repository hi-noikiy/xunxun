<?php

namespace Api\Model;
use Think\Model;

class ProblemModel extends Model{
    /**
     * 获取用户投诉列表
     */
   	public function getList($where){
        $field = "id as problem_id,btypeid,title,createtime";
        return $this->field($field)->where($where)->select();
        /*if($data == 1){
            $field = "id as problem_id,btypeid,title,createtime";
            $where['btypeid'] =  1;
             $this->field($field)->where($where)->select();
        }else if($data==2){
            $typearr = ['1','2'];
            $where['btypeid'] =  ['in', $typearr];
            $field = "id as problem_id,btypeid,title,createtime";
             $this->field($field)->select();
            echo $this->_Sql();die();
        }*/
        // echo $this->_Sql();
   	}
}
