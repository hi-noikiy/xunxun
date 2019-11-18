<?php

namespace Api\Model;
use Think\Model;

class MemberAvatarModel extends Model{

    /**获取当前用户的所有头像
     * @param $user_id
     * @return false|mixed|
     */
    public function getUidAvatar($user_id){
        $where['user_id']  = $user_id;
        $field = "id as avatar_id,photo_url";
        return $this ->field($field) -> where($where)->select();
//        echo $this->_Sql();die();
    }
}
