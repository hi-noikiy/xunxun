<?php
namespace Api\Controller;

use Api\Service\EmoticonService;
use Think\Controller;
use Think\Exception;
use Think\Log;
use Common\Util\RedisCache;

class EmoticonController extends BaseController {
    /**
     * 表情系列列表
     * @param $token token值
     * @param $type  表情类型 1默认 2专属表情
     * @param $signature 签名MD5(小写）
     */
    public function getList($token,$type,$signature){
        //获取数据
        $token = I('post.token');
        $type = I('post.type');
        $signature = I('post.signature');
        $data = [
            "token"=>$token,
            "type" => $type,
            "signature"=>$signature,
        ];
        try{
            if($data['signature'] !== Md5(strtolower($data['token'].$data['type']))){
//                E("验签失败",2000);
            }
            //根据礼物类型查出对应的礼物数据操作
            $moticon_listes = RedisCache::getInstance()->get("list_emoticon");
            if(empty($moticon_listes)){
                $Emoticon = EmoticonService::getInstance() -> getList($data['type']);
                if(!$Emoticon){
                    E("查询失败", 2003);
                }
                foreach($Emoticon as $key=>$value){
                    $Emoticon[$key]['face_image'] = C("APP_URL_image").$value['face_image'];
                    $Emoticon[$key]['animation'] = C("APP_URL_image").$value['animation'];
                    $Emoticon[$key]['game_image'] = $value['game_image'];
                    if($Emoticon[$key]['game_image']){
                        //将字符串转化为数组并且将数组的空值去掉
                        $Emoticon[$key]['game_images'] = array_filter(explode(";",$Emoticon[$key]['game_image']));
                        foreach($Emoticon[$key]['game_images'] as $k=>$v){
                            $Emoticon[$key]['game_images'][$k] =  C("APP_URL_image").$v;
                        }
                    }else{
                        $Emoticon[$key]['game_images'] = [];
                    }
                    unset($Emoticon[$key]['game_image']);       //去掉game_image
                }
                $expired_time = 7 * 24 * 60 * 60; // 单位分钟.
                RedisCache::getInstance()->set("list_emoticon",json_encode($Emoticon));
                RedisCache::getInstance()->expireAt("list_emoticon",$expired_time);     //设置缓存时间
            }else{
                $Emoticon=json_decode($moticon_listes);
            }
            $result = [
                "Emoticon_list" => $Emoticon,
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