<?php

namespace Api\Model;
use Think\Model;

class UserCardModel extends Model{
    
   	public function addData($data){
		return $this -> add( $data );

   	}
}
