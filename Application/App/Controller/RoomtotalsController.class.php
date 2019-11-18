<?php

namespace Api\Controller;

use Think\Controller;
use Api\Service\RoomtotalsService;
use Think\Exception;
use Common\Util\ParamCheck;
use Think\Log;

class RoomtotalsController extends BaseController {
    /**
     * 该房间内的人气值数据及头像
     * @param $token token值
     * @param $room_id 房间Id
     * @param $signature 签名MD5(小写）
     * http://localhost/zhibo/index.php/Api/Roomtotals/getRoomNumber?token=b477cf109e03078c76671e60764818de&room_id=6
     */
    public function getRoomNumber($token,$room_id,$signature){
        //获取数据
        $data = [
            "token"=> I('get.token'),
            "room_id" => I('get.room_id'),
            "signature"=> I('get.signature'),
        ];
        try{
            if($data['signature'] !== Md5(strtolower($data['token'].$data['room_id']))){
                E("验签失败",2000);
            }
            //校验数据
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            //数据操作
            $RoomNumber = RoomtotalsService::getInstance()->getOneByIdField($room_id,"visitor_number");
            $RoomNumber = !empty($RoomNumber) ? $RoomNumber : 1;
            $result = [
                "roomnumber" => $RoomNumber,
            ];
            //查询成功
            $this -> returnCode = 200;
            $this -> returnData= $result;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();

    }






}


?>