<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Api\Service\RoomManagerService;
use Api\Service\LanguageroomService;
use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Think\Log;

class RoomManagerController extends BaseController {
    /**
     * 管理员列表
     * @param token token值
     * @param signature 签名MD5(小写）
     */
    public function getList($token,$room_id,$signature){
        //获取数据
        $token = I('post.token');
        $room_id = I('post.room_id');
        $signature = I('post.signature');
        $data = [
            "token"=> $token,
            "room_id" => $room_id,
            "signature" => $signature,
        ];

        try{
            //校验数据
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            if($data['signature'] !== Md5(strtolower($data['token'].$data['room_id']))){
                E("验签失败",2000);
            }
            //数据操作
//            $data = RoomManagerService::getInstance()->getList();
            $dataes = RoomManagerService::getInstance()->getList($data['room_id']);
            foreach($dataes as $key=>$value){
                $dataes[$key]['avatar'] = C("APP_URL").$value['avatar'];
                $dataes[$key]['is_manager'] = 1;        //是否为管理员 0 非管理员 1管理员
            }
            if(!$dataes){
                $dataes = [];
            }
            $result = [
                "list" => $dataes,
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

    /**添加管理员操作
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     * @param $signature    签名md5(room_id,user_id)
     */
    public function createManager($token,$room_id,$user_id,$signature){
        $data = [
            "token" => I('post.token'),
            "room_id" => I('post.room_id'),
            "user_id" => I('post.user_id'),
            "signature" => I('post.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            ParamCheck::checkInt("user_id",$data['user_id'],1);
            //验证数据
            if($data['signature'] !== Md5(strtolower($data['room_id'].$data['user_id']))){
                E("验签失败",2000);
            }
            //根据房间获取房间房主,并判断房主自己不能设为管理员
            $room_userid = LanguageroomService::getInstance()->getUserField($data['room_id']);
            if($room_userid == $data['user_id']){
                E("房主自己不能设为管理员",2000);
            }
            //每一个房间最多有20个管理员
            $is_number = RoomManagerService::getInstance()->setCount($data['room_id']);
//            var_dump($is_number);die();
            if($is_number>=20){
                E('当前房间管理员数量不能超过20个',2000);
            }
            //添加管理员操作
            $admindata = [
                "rooms_id" => $data['room_id'],
                "user_id" => $data['user_id'],
                "creattime" => time(),
            ];
            $id = RoomManagerService::getInstance()->addData($admindata);
            if(!$id){
                E('添加管理员数据错误',2000);
            }
            //根据当前id获取当前数据
            $list = RoomManagerService::getInstance()->detail($id);
            if(!$list){
                E("管理员失败",2000);
            }
            $result = [
                "admin_list" => $list,
            ];
            $this -> returnCode = 200;
            $this -> returnData=$result;

        }catch (\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
    }
    /**取消管理员操作
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     * @param $signature    签名md5(room_id,user_id)
     */
    public function removeManager($token,$room_id,$user_id,$signature){
        $data = [
            "token" => I('post.token'),
            "room_id" => I('post.room_id'),
            "user_id" => I('post.user_id'),
            "signature" => I('post.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            ParamCheck::checkInt("user_id",$data['user_id'],1);
            //验证数据
            if($data['signature'] !== Md5(strtolower($data['room_id'].$data['user_id']))){
                E("验签失败",2000);
            }
            //取消管理员操作
            $admindata = [
                "room_id" => $data['room_id'],
                "user_id" => $data['user_id'],
            ];
            //判断当前房间有需要取消的管理用户不
            $isManager = RoomManagerService::getInstance()->isManager($admindata);
//            var_dump($isManager);die();
            if(!$isManager){
                E("当前房间内此管理用户不存在",2000);
            }
            //取消管理员数据操作
            RoomManagerService::getInstance()->removeManager($admindata);
            $this -> returnCode = 200;

        }catch (\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
    }

    /**管理中搜索功能(根据用户昵称,用户id)
     * 搜索用户接口.重点监控此带like的接口。根据搜索的压力情况，考虑是否使用elasticsearch(这里应用的是精确搜索功能)
     * @param $token token值
     * @param $search   搜索的值
     * @param $room_id  房间id值
     * @param $signature    签名(md5(token))
     */
    public function ManagerListSearch($token,$search,$room_id,$signature){
        $data = [
            "token" => I("post.token"),
            "search" => I("post.search"),
            "room_id" => I("post.room_id"),
            "signature" => I('post.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            //验证数据
            if($data['signature'] !== Md5(strtolower($data['token'].$data['room_id']))){
                E("验签失败",2000);
            }
            //根据用户昵称及id搜索相关的用户数据
            $member_list = MemberService::getInstance()->SearchMember($data['search']);
            foreach($member_list as $key=>$value){
                $member_list[$key]['avatar'] = C('APP_URL').$value['avatar'];   //用户头像
                $member_list[$key]['is_manager'] = RoomManagerService::getInstance()->isRoomManager($data['room_id'],$value['user_id']);   //当前用户是否为房间的管理员 0非管理员 1管理员\
//                var_dump($member_list[$key]['is_manager']);die();
                if($member_list[$key]['is_manager']!== null){
                    $member_list[$key]['is_manager'] = 1;
                }else{
                    $member_list[$key]['is_manager'] = 0;
                }
                $member_list[$key]['room_id'] = $data['room_id'];
            }
            if(is_null($member_list)){
                $member_list = [];
            }
            $result = [
                "member_list" => $member_list,
            ];
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