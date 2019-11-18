<?php

namespace Api\Model;
use Think\Model;

class PackModel extends Model{
    /**
     * 获取背包列表数据
     */
   	public function getList($user_id){
   	    $where['user_id'] = $user_id;
        $where['pack_num'] = array('gt',0);
        $field = "p.gift_id,p.pack_num,g.gift_name,g.gift_image,g.gift_number,g.gift_coin";
        return $this->field($field)->alias('p')
            ->join("left join __GIFT__ g on g.id=p.gift_id")
            ->where($where)->order('update_time desc')->select();
//         echo $this->_Sql(); die();
   	}

}
