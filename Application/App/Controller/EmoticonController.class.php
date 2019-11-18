<?php
namespace Api\Controller;

use Api\Service\EmoticonService;
use Think\Controller;
use Think\Exception;
use Think\Log;

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
                E("验签失败",2000);
            }
            //根据礼物类型查出对应的礼物数据操作
            $Emoticon = EmoticonService::getInstance() -> getList($data['type']);
            if(!$Emoticon){
                E("查询失败", 5002);
            }
            foreach($Emoticon as $key=>$value){
               /* $Emoticon[$key]['face_image'] = C("APP_URL").$value['face_image'];
                $Emoticon[$key]['animation'] = C("APP_URL").$value['animation'];*/
                $Emoticon[$key]['face_image'] = C("Image_URL").$value['face_image'];
                $Emoticon[$key]['animation'] = C("Image_URL").$value['animation'];
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