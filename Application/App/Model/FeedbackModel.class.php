<?php

namespace Api\Model;
use Think\Model;

class FeedbackModel extends Model{
    /**增加反馈数据表
     * @param $data
     * @return mixed
     */
    public function addData($feedback_data){
        return $this->add($feedback_data);
//        echo $this->_Sql();
    }
}
