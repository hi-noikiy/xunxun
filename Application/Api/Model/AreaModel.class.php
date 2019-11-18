<?php

namespace Api\Model;
use Think\Model;

class AreaModel extends Model{
    /**
     * 获取城市数据
     */
   	public function getList(){
        $where = [
            "level"=>2,
        ];
        return $this ->field('id as area_id,pid,name,first')->where($where) -> select();
   	}

    /**二级菜单功能列表
     * @param $menuid
     * @return false|mixed
     */
    public function getMenuList($menuid){
        $where = [
            "pid" => $menuid,
        ];
        return $this ->field('id as area_id,pid,name,first')->where($where) -> select();
//        echo $this->_Sql();die();
    }
}
