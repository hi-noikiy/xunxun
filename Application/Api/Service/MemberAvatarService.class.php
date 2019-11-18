<?php
namespace Api\Service;

use Api\Model\MemberAvatarModel;
use Think\Service;

class MemberAvatarService extends Service
{
    protected $_modelName = '\Api\Model\MemberAvatarModel';
    /**
     * @var DebarModel
     */
    protected $_model;

    /**
     * 根据用户id查找当前用户所有数据
     */
    public function getUidAvatar($user_id){
        return $this -> _model -> getUidAvatar($user_id);
    }


}