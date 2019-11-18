<?php

namespace Api\Model;
use Think\Model;

class ChargeiosModel extends Model{

   	/**充值列表
   	 */
   	public function getChargeList(){
   	    $chargeInfo=$this->where('id>0')->field('rmb,diamond,present')->select();
   	    $data = array();
   	    if (!empty($chargeInfo)) {
   	        foreach ($chargeInfo as $k => $v) {
   	            $data[$v['rmb']] = $v;
   	        }
   	    }

   	    return $data;
   	}

    /**
   	 * 充值列表
   	 */
   	public function getlists(){
        $field="id as charge_id ,rmb,diamond,present,coinimg,iosflag";
        return $this->field($field)->select();
//   	    echo $this->_Sql();die();
   	}

    /**
     * 获取数据对应的值
     */
    public function getOneByIdField($id,$getfield){
        $where['rmb'] = $id;
        $field = $getfield;
        return $this->where($where)->getField($field);
//        echo $this->_Sql();die();
    }
}
