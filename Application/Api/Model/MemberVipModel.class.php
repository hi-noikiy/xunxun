<?php

namespace Api\Model;
use Think\Model;

class MemberVipModel extends Model{
    /**
     * 获取所有vip列表数据
     */
   	public function getList(){
        return $this ->field('id as vip_id,exp,vip_image,chat_logo,head_logo,car_logo,gift_logo,first_logo,notice_logo') -> select();
//        return $this ->field('id as vip_id,exp,vip_image,chat_logo,chat_image,chat_content,chat_hide_image,head_logo,head_image,head_content,head_hide_image,car_logo,car_image,car_content,car_hide_image,gift_logo,gift_image,gift_content,gift_hide_image,first_logo,first_image,first_content,first_hide_image,notice_logo,notice_image,notice_content,notice_hide_image') -> select();
   	}

    /**
     *  获取所有vip列表数据
     */
    public function getlistes(){
        return $this ->field('id as vip_id,exp') -> select();
    }

    /**根据守护id获取对应的vip经验值数据
     * @param $grade_id
     * @param $getfield
     * @return mixed
     */
    public function getOneByIdField($vip_id,$getfield){
        $where['id'] = $vip_id;
        $field = $getfield;
        return $this->where($where)->getField($field);
    }

}
