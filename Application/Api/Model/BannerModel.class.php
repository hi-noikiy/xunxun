<?php

namespace Api\Model;
use Think\Model;

class BannerModel extends Model{
         /**
         * 获取轮播图数据
         */
   	public function getAppUrl($type){
   	     $where['type'] = $type;
         $where['status'] = 2;
         $field = "id as banner_id,image,linkurl,title";
         return $this->field($field) ->where($where) -> select();
//        echo $this->_Sql();
   	}
}
