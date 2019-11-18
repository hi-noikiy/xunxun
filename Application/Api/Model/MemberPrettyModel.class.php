<?php

namespace Api\Model;
use Think\Model;

class MemberPrettyModel extends Model{
    /**
     * 靓号表
     */
   	public function getList(){
       $res = $this ->where(['status'=>1]) -> select();
       return $res;
   	}


}
