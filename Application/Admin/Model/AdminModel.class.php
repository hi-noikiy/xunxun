<?php

namespace Admin\Model;

use Think\Model;


class AdminModel extends Model{

    /**登录信息值
     * @param $username 账户
     * @param $password     密码
     * @return mixed    返回值
     */
    public function loginAdmin($username,$password){
        $where['username'] = $username;
        $where['password'] = $password;
        return $this->where($where)->find();
//        echo $this->_Sql();die();
    }

    /**修改token值
     * @param $id   id值
     * @param $field    修改
     */
    public function updateById($id,$field){
        $where['id'] = $id;
        $update['admin_token'] = $field;
        return  $this->where($where)->save($update);
//        echo $this->_Sql();die();
    }

    /**根据id获取对应的数据
     * @param $id
     * @return mixed
     */
    public function getOneById($id){
        $where['id'] = $id;
        return $this -> where($where) -> find();
    }

    /**
     * 更新操作
     * @param $id
     * @return bool|false|int
     */
    public function updateDate($id,$data){
        $where = [
            'id' => $id,
        ];
        return $this -> where($where) ->save($data);
        //        echo $this->_Sql();
    }
    /**
     * 更新用户密码
     * @param $id
     * @return bool|false|int
     */
    public function updatepw($where,$password){       
        return $this -> where($where) -> save($password);
    }

    /**
     * 查询一条数据
     * @param $id
     * @return bool|false|int
     */
    public function idFind($where){
        return $this->where($where)->find();
    }


}
