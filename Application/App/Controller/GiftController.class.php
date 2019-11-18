<?php
namespace Api\Controller;

use Api\Service\GiftService;
use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Think\Log;

class GiftController extends BaseController {
    /**
     * 礼物列表
     * @param $token token值
     * @param $type  礼物类型 1礼物 2背包
     * @param $signature 签名MD5(小写）
     */
    public function getList($token,$type,$signature){
        //获取数据
        $token = I('post.token');
        $type = I('post.type');
        $signature = I('post.signature');
        $data = [
            "token" => $token,
            "type" => $type,
            "signature" => $signature,
        ];
        try{
            if($data['signature'] !== Md5(strtolower($data['token'].$data['type']))){
                E("验签失败",2000);
            }
            //校验数据
            ParamCheck::checkInt("type",$data['type'],1);
            //根据礼物类型查出对应的礼物数据操作
            $gift_list = GiftService::getInstance() -> getList($data['type']);
            if(!$gift_list){
                E("查询失败", 5002);
            }
            foreach($gift_list as $key=>$value){
                $gift_list[$key]['gift_image'] = C("Image_URL").$value['gift_image'];
                $gift_list[$key]['gift_animation'] = C("Image_URL").$value['gift_animation'];
                $gift_list[$key]['animation'] = C("Image_URL").$value['animation'];
                /*$gift_list[$key]['gift_image'] = C("APP_URL").$value['gift_image'];
                $gift_list[$key]['gift_animation'] = C("APP_URL").$value['gift_animation'];
                $gift_list[$key]['animation'] = C("APP_URL").$value['animation'];*/
            }
            $result = [
                "gift_info" => $gift_list,
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

    /**向用户发送礼物
     * @param $token    toekn值
     * @param $user_id  用户id
     * @param $gift_id  礼物id
     * @param $count    数量
     * @param $coin     币
     * @param $signature    验签md5(strtotime(token+$user_id,$gift_id))
     */
    public  function send_giftes($token,$user_id,$gift_id,$count,$coin,$signature){
        //获取数据
        $data = [
            "token" => I('post.token'),
            "user_id" => I('post.user_id'),
            "gift_id" => I('post.gift_id'),
            "count" => I('post.count'),
            "coin" => I('post.coin'),
            "signature" => I('post.signature'),
        ];
        try{
            //验签
            if($data['signature'] !== Md5(strtolower($data['token'].$data['user_id'].$data['gift_id']))){
                E("验签失败",2000);
            }
            //当前用户的币是否允许发送礼物
            //当前用户是多个还是一个
            //数据操作
            $data = D('Tags') -> getList();
            // var_dump($data);die();
            if(!$data){
                E("查询失败", 5002);
            }
            //查询成功
            $this -> returnCode = 200;
            $this -> returnData=$data;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
    }






}


?>