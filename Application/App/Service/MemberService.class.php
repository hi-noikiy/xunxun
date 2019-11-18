<?php
namespace Api\Service;

use Api\Model\MemberModel;
use Think\Service;


class MemberService extends Service
{

    protected $_modelName = '\Api\Model\MemberModel';
    /**
     * @var ApplyModel
     */
    protected $_model;

   public function updateLoginTimeIp($id,$sex=null){
        return $this -> _model -> updateLoginTimeIp($id,$sex);
    }
    /*
     * 修改密码
     * */
    public function updatepw($where,$password){
        return $this -> _model -> updatepw($where,$password);
    }

    /**
     * 添加操作
     * @param $data
     * @return mixed
     */
    public function addData($data){
        return $this -> _model -> addData($data);
    }

    public function getUsername($username){
        return $this -> _model -> getUsername($username);
    }

    public function getOneByIdField($id,$getfield){
        return $this -> _model -> getOneByIdField($id,$getfield);
    }
    //根据qopenid查询用户数据
    public function getByqopenid($where,$field){
        return $this -> _model -> getByqopenid($where,$field);
    }
    public function getByuid($username){
        return $this -> _model -> getByuid($username);
    }

    /**搜索功能条件
     * @param $condition
     */
    public function SearchMember($where){
        return $this -> _model -> SearchMember($where);
    }
    
    /**首页搜索条件
     * @param $condition
     */
    public function Search($search,$where,$order="",$limit=""){
        return $this -> _model -> Search($search,$where,$order,$limit);
    }
    /**用户详情接口
     * @param $user_id
     * @return mixed
     */
    public function detail($user_id){
        return $this -> _model -> detail($user_id);
    }

    /**用户个人中心详情接口
     * @param $user_id
     * @return mixed
     */
    public function UserDetail($user_id){
        return $this -> _model -> UserDetail($user_id);
    }







}