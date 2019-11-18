<?php

namespace Api\Model;
use Think\Model;


class LanguageroomModel extends Model{
    /**
     * 添加操作功能
     */
    public function addData($data){
        return $this ->add($data);
//        echo $this->_Sql();die();
    }

    /**
     *根据用户获取房间详情数据
     */
    public function getFind ($id)
    {
        $where = [
            "user_id" => $id,
        ];
        return $this->where($where)->find();
//        echo $this->_Sql();die();
    }

    /**该房间的详情数据
     * @param $room_id
     * @return array|false|mixed|\PDOStatement|string|Model
     */
    public function getDeatil($room_id){
        $where = [
            "id"=>$room_id,
        ];
        $fields = "id as room_id,user_id,room_name,room_desc,room_image,room_welcomes,room_type,room_tags,room_lock,visitor_number";
        return $this->field($fields)->where($where)->find();
//        echo $this->_Sql();die();
    }

    /**统计该类型的房间数
     * @param $room_type
     * @return int|string
     */
    public function  countes($room_type){
        $where = [
            "room_type"=>$room_type,
        ];
        return $this->where($where)->count();
//        echo $this->_Sql();die();
    }

    /**锁定(解锁)房间操作
     * @param $room_id
     * @param $room_lock
     * @return bool|false|int
     */
    public function getByroomidUpdate($room_id,$room_lock){
        $where = [
            "id"=>$room_id,
        ];
        return $this->where($where)->save(array("room_lock"=>$room_lock));
//        echo $this->_Sql();die();
    }

    /**标签属性修改房间操作
     * @param $room_id
     * @param $room_tags
     * @return bool|false|int
     */
    public function getUpdateTags($room_id,$room_tags){
        $where = [
            "id"=>$room_id,
        ];
        return $this->where($where)->save(array("room_tags"=>$room_tags));
//        echo $this->_Sql();die();
    }

    /**修改操作
     * @param $id
     * @param $profile
     * @return bool|false|int
     */
    public function getUpdate($room_id,$result_keys,$result_values){
        $where['id'] = $room_id;
        $update[$result_keys] = $result_values;
        return $this->where($where)->save($update);
//        echo $this->_Sql();die();
    }   
    /**首页搜索房间功能
     * @param $search
     * @return false|mixed|
     */
    public function Search($search,$where,$order="",$limit=""){
        $fields = "id as room_id,user_id,room_name,room_desc,room_image,room_welcomes,room_type,room_tags,room_lock,visitor_number";
        return  $this->field($fields)->where($where)->order($order)->limit($limit)->select();
        //        echo $this->_Sql();
    }

    /**根据id获取对应某一个字段
     * @param $id
     * @param $getfield
     * @return mixed
     */
    public function getUserField($room_id){
        $where['id'] = $room_id;
        return $this->where($where)->getField("user_id");
//        echo $this->_Sql();die();
    }

    /**封装人气值排名
     * @param $limit
     * @return false|mixed
     */
    public function getRedList($limit){
        $where['room_type'] = array('neq',5);
        $fields = "lm.id as room_id,lm.visitor_number,lm.visitor_externnumber,lm.room_name,lm.room_type,lm.room_tags,lm.user_id,room_lock";
//        $fields = "lm.id as room_id,sum(lm.visitor_number + lm.visitor_externnumber) as visitor_number,lm.room_name,lm.room_type,lm.room_tags,lm.user_id,room_lock";
        return $this->field($fields)->alias('lm')
            ->where($where)
            ->order('visitor_number+visitor_externnumber desc')
            ->select();
       /* $fields = "lm.id as room_id,lm.visitor_number,lm.room_name,lm.room_type,lm.room_tags,lm.user_id,room_lock";
        return $this->field($fields)->alias('lm')
            ->where($where)->order('visitor_number desc')->limit($limit)->select();*/

//        echo $this->_Sql();die();
    }

    /**获取房间的类型人气倒叙N条数据
     * @param $data
     */
    public function getBytypeList($limit,$room_type){
        $where['room_type'] = $room_type;
        $fields = "lm.id as room_id,lm.visitor_number,lm.visitor_externnumber,lm.room_name,lm.room_type,lm.room_tags,lm.user_id,room_lock";
        return $this->field($fields)->alias('lm')
            ->where($where)->order('visitor_number+visitor_externnumber desc')->limit($limit) -> select();

//        echo $this->_Sql();die();
    }

    /**获取我创建的房间列表
     * @param $user_id
     * @return mixed
     */
    public function getMyRoom($user_id){
        $where['user_id'] = $user_id;
        $field = "id as room_id,room_name,room_type,room_lock,visitor_number,is_live,user_id";
        return $this ->field($field) ->where($where) ->select();
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
        /**根据id获取对应某一个字段
     * @param $id
     * @param $getfield
     * @return mixed
     */
    public function getroomlist($field){
        return $this->where('id>0')->field($field)->select();
        //        echo $this->_Sql();die();
    }
    
        public function getOneByIdFields($where,$field){
        return $this->where($where)->getField($field);
//        echo $this->_Sql();die();
    }

    /**封装人气值排名
     * @param $limit
     * @return false|mixed
     */
    public function getRedListess($limit){
        $where['room_type'] = array('neq',5);
        $fields = "lm.id as room_id,lm.visitor_number,lm.room_name,lm.room_type,lm.room_tags,lm.user_id,room_lock";
        return $this->field($fields)->alias('lm')
            ->where($where)->order('visitor_number desc')->limit($limit)->select();

//        echo $this->_Sql();die();
    }

}
