<?php
namespace Api\Model;
use Think\Model;

class AppcertificateModel extends Model{
    /**
     * 添加到appstore
     */
    public function addData($data){
        return $this ->add($data);
//        echo $this->_Sql();die();
    }
    
    /**
     * 
     */
    public function updateCertificateStatus($where,$data){
        return $this ->where($where)->save($data);
        //        echo $this->_Sql();die();
    }
}
