<?php

namespace Api\Model;
use Think\Model;

class BeandetailModel extends Model{

    /**添加用户收支明细
     * @param $dataes
     */
    public function addData($dataes){
         return $this -> addAll($dataes);
//        echo $this->_Sql();die();
    }
    public function addDatas($dataes){
        return $this -> add($dataes);
        //        echo $this->_Sql();die();
    }
        /*
     * 查询 消费记录
     */
    public function records($where){
        return $this->where($where)-> count();
    }
        
    /**魅力榜数据
     */
    public function randbeansort($where){
        $field='uid userid,sum(bean) coin,m.nickname,m.avatar,m.sex';
        return   $this->field($field)
        ->join('left join zb_member m on uid=m.id')
        ->where($where)
        ->order('coin desc')
        ->group("uid")
        ->limit(20)
        ->select();
        // $this->_Sql();die();
    }

    /**
     * 统计当前当前起始时间与当前时间的赠送最多的三个用户数据
     */
    public function randmember($where){
        $field='get_uid,sum(bean) coin,m.avatar';
        return  $this->field($field)
            ->join('left join zb_member m on uid=m.id')
            ->where($where)
            ->order('coin desc')
            ->group("get_uid")
            ->limit(3)
            ->select();
//         echo $this->_Sql();die();
    }

    /**
     * 统计当前当前起始时间与当前时间的赠送最多的三个用户数据
     */
    public function rand_member($where){
        $field='get_uid,sum(bean) coin,m.avatar,m.sex,m.nickname';
        return  $this->field($field)
            ->join('left join zb_member m on uid=m.id')
            ->where($where)
            ->order('coin desc')
            ->group("get_uid")
            ->limit(20)
            ->select();
//         echo $this->_Sql();die();
    }
}
