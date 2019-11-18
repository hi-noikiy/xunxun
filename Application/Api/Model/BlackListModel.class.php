<?php

namespace Api\Model;
use Think\Model;

class BlackListModel extends Model{
    /**
     * 获取黑名单
     */
   	public function getList(){
   		$data = array();
       $res = $this ->field('id ,uid,create_time')->where(['status'=>1]) -> select();
       if (empty($res)) {
        	return array();
       }
       foreach ($res as $key => $value) {
       		$data[] = $value['uid'];
       }
       return $data;
   	}


}
