<?php
namespace Api\Controller;

use Api\Service\GiftService;
use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Think\Log;

class ResourceController extends BbaseController {

    /**获取所有礼物接口
     * @param method    GET方式
     */
    public function Allgift(){

        try{
            //获取所有礼物数据
            $gift_listes = RedisCache::getInstance()->get("All_gifts");
            if(empty($gift_listes)){
                $gift_list = GiftService::getInstance() -> getListAll();
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
                }
                $expired_time = 7 * 24 * 60 * 60; // 单位分钟.
                RedisCache::getInstance()->set("All_gifts",json_encode($gift_list));
                RedisCache::getInstance()->expireAt("All_gifts",$expired_time);     //设置缓存时间
            }else{
                $gift_list=json_decode($gift_listes);
            }
            $result = [
                "gift_info" => $gift_list,
            ];
            //查询成功
            $this -> returnCode = 200;
            $this -> returnData = $result;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
    }





}


?>