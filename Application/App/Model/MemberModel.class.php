<?php

namespace Api\Model;

use Think\Model;


class MemberModel extends Model{
   	public function addData($data){

		return $this -> add( $data );
//        echo $this->_Sql();

   	}

    public function getUsername($username){
        $where['username'] = $username;
        return $this->where($where)->getField("username");
//        echo $this->_Sql();die();
    }


    /**
     *获取用户审请数据
     */
    public function getOneById($id){
        $where = [];
        $where['id'] = $id;

        return $this -> where($where) -> find();
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

    /**修改用户信息状态
     * @param $id
     * @param $result_keys
     * @param $result_values
     * @return bool|false|int
     */
    public function updateById($id,$result_keys,$result_values){
        $where['id'] = $id;
        $update[$result_keys] = $result_values;
        return $this->where($where)->save($update);
//        echo $this->_Sql();die();
    }


    /**
     * 查询用户的id
     */
    public function getByuid($username){
        $where = [];
        $where['username'] = $username;

         return $this ->where($where) -> getField("id");
		//echo $this->_Sql();die();
    }
    /**
     * 根据qopenid查询用户数据
     */
    public function getByqopenid($where,$field){       
        return $this ->where($where) ->Field($field)->select();
        //echo $this->_Sql();die();
    }

    /**
     * 更新用户的登录时间与Ip地址
     * @param $id
     * @return bool|false|int
     */
    public function updateLoginTimeIp($id,$sex=null){
        $where = [
            'id' => $id,
        ];
        if($sex != null){
         $saveData['sex'] = $sex;
        }
        $time=date('Y-m-d H:i:s',time());
        $saveData['login_time'] = $time;
        $saveData['login_ip'] = gets_client_ip();
        return $this -> where($where) -> save($saveData);
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
     * 获取该用户的详细信息
     * @param $id
     * @return array|false|mixed|\PDOStatement|string|Model
     */
    public function detail($id){
        $where = [
            "id"=>$id,
        ];
        $field = "id as user_id,nickname,sex,avatar,is_vip";
        return $this->field($field)->where($where)->find();
//        echo $this->_Sql();
    }

    /**管理员搜索功能
     * @param $where
     * @return false|mixed|
     */
    public function SearchMember($where){
        if (ctype_digit($where)) {
            $where = array("id = ' " . $where . " ' or nickname = '" . $where . "'");
        } else {
            $where = array("nickname = '" . $where . "'");
        }
        $field = "id as user_id,nickname,avatar,sex";
        return  $this->field($field)->where($where)->select();
//        echo $this->_Sql();
    }
    
    /**首页搜索用户功能
     * @param $search
     * @return false|mixed|
     */
    public function Search($search,$where,$order="",$limit=""){
//         if (ctype_digit($search)) {
//             $where = array("id" =>$search);         
//         } else {
           
//             $where['nickname'] = array("LIKE", '%' . $search . '%');
//         }
        $field = "id as user_id,nickname,avatar,sex,lv_dengji,is_vip";
        return  $this->field($field)->where($where)->order($order)->limit($limit)->select();
        //        echo $this->_Sql();
    }

    /**
     *获取该用户的详细信息
     */
    public function getOneByIds($ids){
        $where['id'] = array("in",$ids);
        return $this -> where($where) -> select();
//        echo $this->_Sql();die();
    }

    /**
     * 获取该个人中心用户的详细信息
     * @param $id
     * @return array|false
     */
    public function UserDetail($user_id){
        $where = [
            "id"=>$user_id,
        ];
//        $field = "id as user_id,username,nickname,sex,intro,avatar,is_vip,status,role,roomnumber,totalcoin,diamond,birthday,city,attestation,mobile,login_status,login_time";
        $field = "id as user_id,fansmenustatus,username,nickname,sex,intro,avatar,status,role,roomnumber,totalcoin,freecoin,diamond,free_diamond,birthday,city,attestation,lang_approve,mobile,online,login_time";
        return $this->field($field)->where($where)->find();
//        echo $this->_Sql();die();
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
     * 给用户的某个字段加值
     * @param $id
     * @return array|false
     */
    public function setInccoin($where,$num,$field){
        return $this->where($where)->setInc($field,$num);
    }
    
    /**
     * 给用户的某个字段减值
     * @param $id
     * @return array|false
     */
    public function setDecdiomond($where,$num,$field){
        return $this->where($where)->setDec($field,$num);
    }

    /**获取当前用户的vip相关的数据
     * @param $user_id
     * @return array|
     */
    public function user_vipinfo($user_id){
        $where = [
            "id"=>$user_id,
        ];
        $field='id as user_id,username,sex,nickname,intro,long_day,vip_buytime';
        return $this->field($field)->where($where)->find();
    }

    /**统计当前用户的经验值数据
     * @param $user_id  用户id
     * @return array
     */
    public function merge_exp($user_id){
        $where = [
            "id"=>$user_id,
        ];
        $field='IFNULL(exp_values,0) + IFNULL(exp_admin_values,0) as total_expvalue';
        return $this->field($field)->where($where)->find();
//        echo $this->_Sql();
    }

    /**统计所有用户的经验值数据
     * @return false
     */
    public function all_exp(){
        $field='IFNULL(exp_values,0) + IFNULL(exp_admin_values,0) as total_expvalue';
//        return $this->field($field)->select();
        return $this->field($field)->order('total_expvalue asc')->group('total_expvalue')->having('total_expvalue>=0')->select();
//        return $this->field($field)->having('total_expvalue>0')->select();
//        echo $this->_Sql();
    }

    /**剩下虚拟币
     * @param $user_id  用户id
     * @return false
     */
    public function totalcoin($user_id){
        $where['id'] = $user_id;
        $field='IFNULL(totalcoin,0) - IFNULL(freecoin,0) as total_expvalue';
        return $this->field($field)->where($where)->select();
    }

    /**剩下钻石
     * @param $user_id  用户id
     * @return false
     */
    public function diamond($user_id){
        $where['id'] = $user_id;
        $field='IFNULL(diamond,0) - IFNULL(free_diamond,0) as total_expvalue';
        return $this->field($field)->where($where)->select();
    }

    /**等级定时任务
     * @param $uptime
     * @return false
     */
    public function grade_timer($uptime){
        /*$where['charge_time'] = array('neq',$uptime['end_time']);
        $field = "id as user_id,totalcoin,is_vip,grade_coin,DATE_FORMAT(charge_time,'%Y-%m-%d') as charge_time";
         $this->field($field)->where($where)->select();
        echo $this->_Sql();die();*/
        $where['charge_time'] = array('lt',$uptime);
        $field = "id as user_id,totalcoin,is_vip,grade_coin,DATE_FORMAT(charge_time,'%Y-%m-%d') as charge_time";
        return $this->field($field)->where($where)->select();
//        echo $this->_Sql();die();
    }
}
