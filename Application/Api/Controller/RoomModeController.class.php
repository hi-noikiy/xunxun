<?php
namespace Api\Controller;

use Think\Controller;
use Think\Exception;
use Think\Log;
use Common\Util\RedisCache;

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
            $list_mode = RedisCache::getInstance()->get("list_mode");
            if(empty($list_mode)){      //读取数据库
                $room_mode = D('RoomMode')->getListes();
                $expired_time = 7 * 24 * 60 * 60; // 单位分钟.
                RedisCache::getInstance()->set("list_mode",json_encode($room_mode));
                RedisCache::getInstance()->expireAt("list_mode",$expired_time);     //设置缓存时间
            }else{      //从缓存里获取数据
                $room_mode=json_decode($list_mode);
            }
//            $room_mode = D('RoomMode')->getListes();
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