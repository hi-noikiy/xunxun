<?php

namespace Api\Model;
use Think\Model;


class TagsModel extends Model{

    /**
     * 获取标签数据
     */
   	public function getList(){
        return $this ->field("id as tag_id,tag_name") -> select();
        // echo $this->_Sql(); 	//调试sql 语句 __RECOMMEND_CAR__
   	}

    /**根据id获取对应某一个字段
     * @param $id
     * @param $getfield
     * @return mixed
     */
    public function getOneByIdField($id,$getfield){
        $where['id'] = $id;
        $field = $getfield;
        return $this->where($where)->getField($field);
//        echo $this->_Sql();die();
    }

    /**根据分类id获取对应分类下的标签
     * @param $mode_id  分类id
     * @return false
     */
    public function getListById($mode_id){
        $where['mode_id'] = $mode_id;
        $field = "id as tags_id,tag_name";
        return $this->field($field)->where($where)->select();
//        echo $this->_Sql();die();

    }



}
