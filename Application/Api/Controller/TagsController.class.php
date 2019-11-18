<?php

namespace Api\Controller;

use Api\Service\TagsService;
use Api\Service\LanguageroomService;
use Api\Service\MemberService;
use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Think\Log;

class TagsController extends BaseController {
    /**
     * 标签列表
     * @param token token值
     * @param signature 签名MD5(小写）
     */
    public function getTagsList($token,$signature=null){
        //获取数据
        $token = I('post.token');
        $signature = I('post.signature');
        $data = [
            "token" => $token,
            "signature" => $signature,
        ];
        try{
            //版本
            $isVersion = true;
            $banben = explode(',', $this->clientVersion);
            if(version_compare($banben[0],'2.2.0', '<')){
                $isVersion = false;
            }
            /*if($data['signature'] !== Md5(strtolower($data['token']))){
                E("验签失败",2000);
            }*/
            // $moshi = [
            //     ['mode_id'=>5,'room_mode'=>'多人模式','room_type'=>1],
            //     ['mode_id'=>6,'room_mode'=>'单人模式','room_type'=>2],
            // ];
            //数据操作
            $status = 2;
            $mode_list = D('RoomMode') -> getList($status);
            $tag_list = [];
            foreach($mode_list as $key=>$value){
                if ($isVersion) {
                    if ($value['mode_id'] == 5) {
                        $mode_list[$key]['room_mode'] = '多人模式';
                    }
                    if ($value['mode_id'] == 6) {
                        $mode_list[$key]['room_mode'] = '单人模式';
                    }
                    $mode_list[$key]['mode_tags'] = D('tags')->getListById($value['mode_id']);
                }else{
                    if ($value['mode_id'] == 5) {
                        $mode_list[$key]['mode_tags'] = D('tags')->getListById($value['mode_id']);
                    }else{
                        unset($mode_list[$key]);
                    }
                }
                
            }

            $result = [
                "mode_list" => $mode_list,
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

    /**房间标签设置接口及修改房间标签接口(如果房间标签修改时传tags_id)
     * @param $token    token值
     * @param $tags_id  标签id
     * @param $room_id  房间id
     * @param $signature    签名MD5(小写）
     */
    public function setRoomTags($token,$tags_id=null,$room_id,$signature){
        //获取数据
        $data = [
            "token" => I('post.token'),
            "tags_id" => I('post.tags_id'),
            "room_id" => I('post.room_id'),
            "signature" => I('post.signatrue'),
        ];
        try{
            if($data['signature'] !== Md5(strtolower($data['token'].$data['room_id']))){
                E("验签失败",2000);
            }
            //如果用户传tags_id，那么就修改房间标签操作
            if($data['tags_id']){
                //校验数据
                ParamCheck::checkInt("tags_id",$data['tags_id'],1);
                //修改房间标签
                LanguageroomService::getInstance()->getUpdateTags($data['room_id'],$data['tags_id']);
                //返回当前房间数据
                $tags_list= is_nulldata(LanguageroomService::getInstance() -> getDeatil($data['room_id']));
            }else{
                //校验数据
                ParamCheck::checkInt("room_id",$data['room_id'],1);
                //获取当前房间的标签
                $room_info = LanguageroomService::getInstance()->getDeatil($data['room_id']);
                //查询当前房间的标签信息
                $tags_list = TagsService::getInstance()->getList();
                foreach($tags_list as $key=>$value){
                    if($room_info['room_tags']){
                        if($room_info['room_tags'] == $tags_list[$key]['tag_id']){
                            $tags_list[$key]["default_tags"] = 1;   //默认标签标识
                        }else{
                            $tags_list[$key]["default_tags"] = 0;   //未选 中标签标识
                        }
                    }else{
                        //取出当前创建房间人的个人信息里面的性别数据
                        $data['sex'] = MemberService::getInstance()->getOneByIdField($room_info['user_id'],"sex");
                        if($tags_list[$key]['tag_id'] == $data['sex']){
                            $tags_list[$key]["default_tags"] = 1;   //默认标签标识
                        }else{
                            $tags_list[$key]["default_tags"] = 0;   //未选 中标签标识
                        }
                    }

                }


            }
            $result = [
                "tags_list" => $tags_list,
            ];
            //查询成功
            $this -> returnCode = 200;
            $this -> returnData=$result;
        }catch (\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();
    }






}


?>