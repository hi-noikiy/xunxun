<?php
namespace Api\Controller;

use Api\Service\GiftService;
use Api\Service\CoindetailService;
use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Think\Log;
use Common\Util\RedisCache;

class GiftController extends BaseController {

    //收到礼物列表
    public function giftRankList()
    {
        $token = I('post.token');
        $touid = I('post.touid');
        try {
            if (!$token) {
                E("参数错误", 500);
            }
            $giftList = GiftService::getInstance()->getGiftList();
            if (empty($giftList)) {
                E("数据异常", 500);
            }
            
            $userid = RedisCache::getInstance()->get($token);
            if ($touid) {
                $userid = $touid;
            }

            $black = array('1000009','1000006');
            if (in_array($userid, $black)) {
                $result = [];
            }else{
                $result = CoindetailService::getInstance()->get_gift($userid,$giftList);
            }

            $this -> returnCode = 200;
            $this -> returnData=$result;
            $this->returnData();

        } catch (Exception $e) {
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
        
    }

    

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
//            if($data['signature'] !== Md5(strtolower($data['token'].$data['type']))){
//                E("验签失败",2000);
//            }
            //校验数据
            ParamCheck::checkInt("type",$data['type'],1);
            //根据礼物类型查出对应的礼物数据操作
            $gift_listes = RedisCache::getInstance()->get("list_gifts");
            if(empty($gift_listes)){
                $gift_list = GiftService::getInstance() -> getList($data['type']);
                if(!$gift_list){
                    E("查询失败", 2003);
                }
                foreach($gift_list as $key=>$value){
                    $gift_list[$key]['gift_image'] = C("APP_URL_image").$value['gift_image'];
                    if($value['gift_animation']){
                        $gift_list[$key]['gift_animation'] = C("APP_URL_image").$value['gift_animation'];
                    }else{
                        $gift_list[$key]['gift_animation'] = "";
                    }
                    if($value['animation']){
                        $gift_list[$key]['animation'] = C("APP_URL_image").$value['animation'];
                    }else{
                        $gift_list[$key]['animation'] = "";
                    }
//                    RedisCache::getInstance()->hset("list_gifts",$key,json_encode($value));
                }
                $expired_time = 7 * 24 * 60 * 60; // 单位分钟.
                RedisCache::getInstance()->set("list_gifts",json_encode($gift_list));
                RedisCache::getInstance()->expireAt("list_gifts",$expired_time);     //设置缓存时间
            }else{
                $gift_list=json_decode($gift_listes);
//                $gift_list = [];
//                foreach($gift_listes as $keys=>$values){
//                    array_push($gift_list,json_decode($values,true));
//                }
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

    /**根据礼物id获取礼物详情
     * @param $token    token值
     * @param $gift_id  礼物id
     * @param $signature    验签(md5(strtolower(token+gift_id)))
     */
    public function giftdetail($token,$gift_id,$signature){
        //获取数据值
        $data = [
            "token" => I('post.token'),
            "gift_id" => I('post.gift_id'),
            "signature" => I('post.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("gift_id",$data['gift_id'],1);
            //验签
            if($data['signature'] !== Md5(strtolower($data['token'].$data['gift_id']))){
                E("验签失败",2000);
            }
            //根据礼物id获取礼物详情
            $gift_detail = D('gift')->getByidDetail($data['gift_id']);
            if($gift_detail){
                $gift_detail['gift_image'] = C("APP_URL_image").$gift_detail['gift_image'];
                $gift_detail['gift_animation'] = C("APP_URL_image").$gift_detail['gift_animation'];
                $gift_detail['animation'] = C("APP_URL_image").$gift_detail['animation'];
            }else{
                E("该当前礼物不存在",2000);
            }
            //查询成功
            $this -> returnCode = 200;
            $this -> returnData = $gift_detail;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
    }






}


?>