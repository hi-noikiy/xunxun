<?php
namespace Api\Controller;

use Think\Controller;
use Think\Exception;
use Think\Log;

class RoomModeController extends BaseController {
    /**
     * 分类列表
     * @param $token token值
     * @param $signature 签名MD5(小写）
     */
    public function getList($token,$signature){
        //获取数据
        $data = [
            "token"=> I('post.token'),
            "signature"=> I('post.signature'),
        ];
        try{
           /* if($data['signature'] !== Md5(strtolower($data['token']))){
                E("验签失败",2000);
            }*/
            //所有房间分类
            $room_mode = D('RoomMode')->getListes();
            $result = [
                "room_mode" => $room_mode,
            ];
            //查询成功
            $this -> returnCode = 200;
            $this -> returnData=$result;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

    }

}


?>