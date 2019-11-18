<?php

namespace Api\Controller;

use Api\Service\MemberService;
use Api\Service\CoindetailService;
use Api\Service\LanguageroomService;
use Api\Service\RoomdukeService;
use Api\Service\DukeService;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Think\Controller;


class DukeController extends BaseController {

    /**会员中心爵位列表接口
     * @param $token    token值
     * @param $signature    签名(md5(strtolower(token)))
     * 接口说明:统计所有消费记录里面房间与用户的数据
     */
	public function getList($token,$signature){
        $data = [
            "token" => I('post.token'),
            "signature" => I('post.signature'),
        ];
		try{
            if($data['signature']!== md5(strtolower($data['token']))){
                E("验签失败",2000);
            }
            $user_id = RedisCache::getInstance()->get($data['token']);
//            var_dump($user_id);die();
			//房间统计数据操作
//            $roomduke_list = CoindetailService::getInstance()->getRoomduke($user_id);
            $roomduke_list = RoomdukeService::getInstance()->getRoomduke($user_id);
            foreach($roomduke_list as $k=>$v){
//                $item[$v['coin']] = $v['coin'];
                $roomduke_list[$k]['room_id'] = $v['room_id']; //房间id
                $roomduke_list[$k]['coin'] = $v['duke_coins']; //消费
                $roomduke_list[$k]['room_name'] = LanguageroomService::getInstance()->getOneByIdField($v['room_id'],"room_name");  //房间名称
                //根据房间获取创建人的用户头像
                $values['user_id'] = LanguageroomService::getInstance() -> getOneByIdField($v['room_id'],"user_id");
                $roomduke_list[$k]['room_image'] = MemberService::getInstance()->getOneByIdField($values['user_id'],"avatar");       //用户头像
                $roomduke_list[$k]['room_image'] = getavatar($roomduke_list[$k]['room_image']);
                //获取当前房间的分类
                $values['room_type'] = LanguageroomService::getInstance() -> getOneByIdField($v['room_id'],"room_type");
                $roomduke_list[$k]['room_type'] = D('RoomMode')->getOneByIdField($values['room_type'],"room_mode");       //房间分类属性
                //获取当前爵位等级制度
                /*$duek_result = $this->dukedengji($roomduke_list[$k]['coin']);
                $roomduke_list[$k]['duke_id'] = $duek_result['duke_id'];
                $roomduke_list[$k]['duke_image'] = $duek_result['duke_image'];*/
                $roomduke_list[$k]['duke_id'] = D("RoomDuke")->getOneByIdField($v['room_id'],"grade");
                $roomduke_list[$k]['duke_image'] = C("APP_URL").D('Duke')->getOneByIdField($roomduke_list[$k]['duke_id'],"duke_image");
                /*$duke_listes = D('Duke')->getlist();
                $duke_list = array_column($duke_listes,'duke_coin', 'duke_id');
                arsort($duke_list);        //保持键/值对的逆序排序函数
                $duke_coin = array_flip($duke_list);   //反转数组
                $roomduke_list[$k]['duke_id'] = $this->dukefun( $roomduke_list[$k]['coin'],$duke_coin);
                $roomduke_list[$k]['duke_image'] = C("APP_URL").D('Duke')->getOneByIdField($roomduke_list[$k]['duke_id'],"duke_image");*/
//                var_dump($duke_coin);
                /*if($item[$v['coin']]<1000){
                    unset($roomduke_list);
                }*/
                /*$list[] =  $roomduke_list[$k];
                array_values($roomduke_list[$k]);*/
                unset($roomduke_list[$k]['duke_coins']);
                unset($roomduke_list[$k]['grade']);

            }
            if(empty($roomduke_list)){
                $roomduke_list = [];
            }

            //用户统计数据操作
          /*  $memberduke_list =  CoindetailService::getInstance()->getMemberduke($user_id);
            foreach($memberduke_list as $k=>$v){
                $memberduke_list[$k]['user_id'] = $v['touid']; //用户id
                $memberduke_list[$k]['nickname'] = MemberService::getInstance()->getOneByIdField($v['touid'],"nickname");  //用户名称
                $memberduke_list[$k]['avatar'] = MemberService::getInstance()->getOneByIdField($v['touid'],"avatar");       //用户头像
                $memberduke_list[$k]['avatar'] = getavatar($memberduke_list[$k]['avatar']);
                //获取当前爵位等级制度
                $duek_result = $this->dukedengji($memberduke_list[$k]['coin']);
                $memberduke_list[$k]['duke_id'] = $duek_result['duke_id'];
                $memberduke_list[$k]['duke_image'] = $duek_result['duke_image'];
                unset($memberduke_list[$k]['touid']);
            }
            if(empty($memberduke_list)){
                $memberduke_list = [];
            }*/
            $result = [
                "roomduke_list" => $roomduke_list,
//                "memberduke_list" => $memberduke_list,
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

    /**当前爵位等级详细信息
     * @param $token    token值
     * @param $room_id    房间id
     * @param $user_id    用户id
     * @param $duke_id      等级id
     * @param $coin         虚拟币
     * @param null $signature   签名md5(strtolower($token+$duke_id))
     */
    public function duke_info($token,$room_id=null,$user_id=null,$duke_id,$coin,$signature){
        //获取数据
        $data = [
            "token" => I('post.token'),
            "room_id" => I('post.room_id'),
            "user_id" => I('post.user_id'),
            "duke_id" => I('post.duke_id'),
            "coin" => I('post.coin'),
            "signature" => I('post.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("duke_id",$data['duke_id'],1);
            //验签数据
            if($data['signature'] !== Md5(strtolower($data['token'].$data['duke_id']))){
                E("验签失败",2000);
            }
            //获取名称
            if($data['room_id']){
                $name = LanguageroomService::getInstance() -> getOneByIdField($data['room_id'],"room_name");
            }
            //用户名称
            if($data['user_id']){
                $name =  MemberService::getInstance()->getOneByIdField($data['user_id'],"nickname");
            }
            //获取当前房间或者用户爵位等级详情制度
            $duke_listes = D('Duke')->getFind($data['duke_id']);
//            var_dump($duke_listes);die();
            $duke_listes['duke_image'] =  C("APP_URL").$duke_listes['duke_image']; //当前等级
            $duke_listes['duke_dengji'] = $duke_listes['id']; //当前等级
            $duke_listes['dukeup_dengji'] = $duke_listes['id']+1; //当前离下一个等级
            $duke_listes['duke_number'] = $data['coin']; //当前虚拟币
            $duke_listes['duekup_number'] = DukeService::getInstance()->getOneByIdField($duke_listes['dukeup_dengji'],"duke_coin"); //当前下一个等级虚拟币
            $duke_listes['name'] = $name;
            unset($duke_listes['id']);
            $result = [
                "duke_info" => $duke_listes,
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

    //获取当前爵位等级制度
    private function dukedengji($tatalcoin){
        $duke_listes = D('Duke')->getlist();
        $duke_list = array_column($duke_listes,'duke_coin', 'duke_id');
        arsort($duke_list);        //保持键/值对的逆序排序函数
        $duke_coin = array_flip($duke_list);   //反转数组
        $duke_id = $this->dukefun( $tatalcoin,$duke_coin);
        $duke_image= C("APP_URL").D('Duke')->getOneByIdField($duke_id,"duke_image");

        $result = [
            "duke_id" => $duke_id,
            "duke_image" => $duke_image,
        ];
        return $result;
    }

    //爵位等级方法
    private function dukefun($gf,$arr){
        foreach ($arr as $key => $value)
        {
            if ($gf >= $key)
            {
                return $value;
            }
        }
    }
}


?>