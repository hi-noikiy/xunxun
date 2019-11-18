<?php

namespace Api\Controller;


use Api\Service\RoomMemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Think\Exception;
use Think\Log;

class RoomMemberController extends BaseController {
    /**
     * 该当前房间内在线用户列表:get方式
     * @param $token token值
     * @param $room_id 房间id
     * @param $signature 签名MD5(小写） token+room_id
     * http://local.yd.com/index.php/Api/RoomMember/getList?token=42d4cd19b7e3e7e53563ca9b41843533&room_id=8&page=1
     */
    public function getList($token,$room_id,$page,$signature=null){
        //获取数据
        $data = [
            "token" => I('get.token'),
            "room_id" => I('get.room_id'),
            "signature" => I('get.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            ParamCheck::checkInt("page",$page,1);
            /*if($data['signature'] !== Md5(strtolower($data['token'].$data['room_id']))){
                E("验签失败",2000);
            }*/
            // 每页条数
            $size = 1;
            $pageNum = empty($size)?20:$size;
            $page = empty($page)?1:$page;
            $where['rooms_id'] = $data['room_id'];
            $count =RoomMemberService::getInstance()->countes($where['rooms_id']);
            // 总页数.
            $totalPage = ceil($count/$pageNum);
            // 页数信息.
            $pageInfo = array("page" => $page, "pageNum"=>$pageNum, "totalPage" => $totalPage);
            $limit = ($page-1) * $size . "," . $size;
            //数据操作
            $list = RoomMemberService::getInstance()->getList($data['room_id'],$limit);
            if($list){
                foreach($list as $key=>$value){
                    $list[$key]['avatar'] =C('APP_URL').$value['avatar'];
                    $list[$key]['lv_dengji'] = '5';
                }
            }else{
                $list = [];
            }
            // var_dump($data);die();
            if(!$list){
                $list = [];
            }
            $result = [
                "online_list"=>$list,
                "pageInfo"=>$pageInfo,
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

    /**socket 接口
     * @param $token token值
     * 返回值说明:返回当前的ip地址,返回端口地址
     */
    public function hostconfig($token){
        $data = [
            "token" => I('post.token'),
        ];
        try{
            $app_ip = C("APP_IP");  //sockert地址
            $app_port = C("APP_PORT");  //socket端口
            $result = [
                "server_ip" => $app_ip,
                "server_port" => $app_port,
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