<?php

namespace Api\Controller;

use Api\Service\DebarService;
use Api\Service\MemberService;
use Mockery\Exception;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Think\Log;


class DebarController extends BaseController {
    /**
     * 禁止(禁言列表)
     * @param $token token值
     * @param $type type 1禁入 2禁言
     * @param $room_id 房间id
     * @param $signature 签名 md5(token+type)
     * http://local.yd.com/index.php/Api/Debar/getList?token=b226c0bd538fa1c76e22100e0706d27b&type=1&room_id=6
     */
	public function getList($token,$type,$room_id,$signature){
        //获取数据
        $data = [
            "token"=>I('post.token'),
            "type"=>I('post.type'),
            "room_id"=>I('post.room_id'),
            "signature"=>I('post.signature'),
        ];
        $dataes = [
            "type"=>$data['type'],
            "room_id"=>$data['room_id'],
        ];
		try{

            //校验数据
            ParamCheck::checkInt("type",$data['type'],1);
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            //校验签名
            if($data['signature'] !== md5(strtolower($data['token'].$data['type']))){
                E("验签失败",2000);
            }
			//数据操作
			$debar = DebarService::getInstance()->getList($dataes);
            //根据id查询当前用户数据
            $datalist = [];
            foreach($debar as $key=>$value){
                $datalist[$key]['user_id'] = $value['user_id']; //用户id
                $datalist[$key]['nickname'] = MemberService::getInstance()->getOneByIdField($value['user_id'],"nickname");  //用户名
                $datalist[$key]['avatar'] =C("APP_URL").MemberService::getInstance()->getOneByIdField($value['user_id'],"avatar");  //用户头像
            }
			// var_dump($data);die();
            if(!$datalist){
                $datalist = [];
            }
            $result = [
                "list" => $datalist,
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

    /**禁言或禁入接口
     * @param $token    token值
     * @param $user_id  用户id
     * @param $room_id  房间id
     * @param $type 1禁入 2禁言
     * @param null $signature   签名 md5(token+user_id)
     * local.yd.com/index.php/Api/Debar/setban
     */
    public function setban($token,$user_id,$room_id,$type,$signature){
        //获取数据
        $data = [
            "token" => I("post.token"),
            "user_id" => I('post.user_id'),
            "room_id" => I('post.room_id'),
            "type" => I('post.type'),
            "signature" => I('post.signature'),
        ];
//        $user_info['id'] = RedisCache::getInstance()->get($token);    //用户id
        try{
            //校验数据
            ParamCheck::checkInt("user_id",$data['user_id'],1);
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            ParamCheck::checkInt("type",$data['type'],1);
            //校验签名
            if($data['signature'] !== md5(strtolower($data['token'].$data['user_id']))){
                E("验签失败",2000);
            }
            //自己对自己不能禁入(管理员)
            /*if($data['type'] ==1){
                if($user_info['id'] == $user_id){
                    E("当前对自己不能禁入",2000);
                }
            }
            //自己对自己不能禁言(管理员)
            if($data['type'] ==2){
                if($user_info['id'] == $user_id){
                    E("当前对自己不能禁言",2000);
                }
            }*/

            //todo判断当前用户是否被禁言或者是禁入状态中
            $dataes = [
                "user_id" => $data['user_id'],
                "room_id" => $data['room_id'],
                "type" => $data['type'],
            ];
            //该当前用户在此房间内是否已禁言
            $isDear = DebarService::getInstance()->isDebar($dataes);
            if($isDear){
                E("该用户已禁言",2000);
            }
            //数据操作禁言禁入
            $debar = DebarService::getInstance()->addData($dataes);
            if($debar){
                $this -> returnCode = 200;
                $this -> returnMsg = "禁言成功";
            }
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData($this -> returnMsg);
    }

    /**解除禁言和禁入功能
     * @param $token    token值
     * @param $room_id  房间id
     * @param $type     类型1禁入 2禁言
     * @param $user_id  用户id
     * @param $signature    签名（md5(token+room_id)）
     */
    public function relieve($token,$room_id,$type,$user_id,$signature){
        //获取数据
        $data = [
            "token" => I('post.token'),
            "room_id" => I('post.room_id'),
            "type" => I('post.type'),
            "user_id" => I('post.user_id'),
            "signature" => I('post.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("user_id",$data['user_id'],1);
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            ParamCheck::checkInt("type",$data['type'],1);
            //校验签名
            if($data['signature'] !== md5(strtolower($data['token'].$data['user_id']))){
                E("验签失败",2000);
            }
            //数据操作禁言禁入
            $dataes = [
                "user_id" => $data['user_id'],
                "room_id" => $data['room_id'],
                "type" => $data['type'],
            ];
            //该当前用户在此房间内是否存在
            $isDear = DebarService::getInstance()->isDebar($dataes);
            if(!$isDear){
                E("该用户不存在",2000);
            }
            DebarService::getInstance()->setDel($dataes);
            $this -> returnCode = 200;
            $this -> returnMsg = "解禁成功";
        }catch (\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData($this -> returnMsg);
    }

    /**禁言禁入列表搜索
     * @param $token    token值
     * @param $search   搜索值
     * @param $room_id  房间id
     * @param null $signature   签名md5(token+room_id)
     */
    public function SpeakListSearch($token,$search,$room_id,$signature=null){
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
            //根据用户昵称及id搜索相关的用户数据(如果管理员搜索出此房间创始人如何展示)
            $member_list = MemberService::getInstance()->SearchMember($data['search']);
            foreach($member_list as $key=>$value){
                $member_list[$key]['avatar'] = C('APP_URL').$value['avatar'];   //用户头像
                $member_list[$key]['is_speak'] = DebarService::getInstance()->isRoomSpeak($data['room_id'],$value['user_id']);   //当前用户是否被此房间禁言 0非禁主 1禁言
                if($member_list[$key]['is_speak']){
                    $member_list[$key]['is_speak'] = 1;
                }else{
                    $member_list[$key]['is_speak'] = 0;
                }
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