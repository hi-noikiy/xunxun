<?php

namespace Api\Controller;
use Think\Controller;


class AreaController extends BaseController {
    /**城市列表接口
     * @param $token    token值
     * @param $signature    签名(md5(strtolower(token)))
     */
    public function getArea($token,$signature=null){
        $data = [
            "token" => I('post.token'),
            "signature" => I('post.signature'),
        ];
        try{
            /*if($data['signature']!== md5(strtolower($data['token']))){
                E("验签失败",2000);
            }*/
            //数据操作(层级 0 1 2 省市区县 level=2)
            $Area_list= D('area') -> getList();
            foreach($Area_list as $key=>$value){
                $Area_list[$key]["menu"] = D('area') ->getMenuList($value['area_id']);
            }
            // var_dump($data);
            if(!$data){
                E("查询失败", 5002);
            }
            //查询成功
            $this -> returnCode = 200;
            $this -> returnData = $Area_list;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

    }

    /**
     * 定时任务对应的请求数据
     */
    public function grade_timer(){
        //获取当前时间
        /*$end_time = date("Y-m-d",time());
        $star_time = date("Y-m-d 00:00:00",time());*/
        $end_time = date("Y-m-d 00:00:00",strtotime("-1 day"));
        $uptime = [
            "end_time" => $end_time,
        ];
        try{
            //查询当前充值表里所有的用户
//            $list = D("member")->grade_timer($uptime);
            $list = D("member")->grade_timer($end_time);
            foreach($list as $key=>$value){
                //根据用户的is_vip等级来查询当前未充值所消耗的值
                $uncharge= D('GradeDiamond')->getOneByIdField($value['is_vip'],"uncharge");
                //根据不同用户不同所消耗值来更改用户那grade_coin值
                $grade_coin = D("member")->getOneByIdField($value['user_id'],"grade_coin");
                //如果当前用户虚拟币与消耗值相等时,这个grade_coin就不会往上加了
                $update = [
                    "grade_coin" => $grade_coin - $uncharge,
                ];
                if($update['grade_coin'] < 0){
                    $updates['grade_coin'] = 0;
                    $list[$key]['grade_coin'] = D("member")->updateDate($value['user_id'],$updates);
                }else{
                    $list[$key]['grade_coin'] = D("member")->updateDate($value['user_id'],$update);
                }
                /*$update = [
                    "grade_coin" =>  $uncharge + $grade_coin,
                ];
                if($update > $value['totalcoin']){
                    $updates = [
                        "grade_coin" => $value['totalcoin'],
                    ];
                    $list[$key]['grade_coin'] = D("member")->updatedata($value['user_id'],$updates);
                }else{
                    $list[$key]['grade_coin'] = D("member")->updatedata($value['user_id'],$update);
                }*/
                /* if($list[$key]['totalcoin'] >= $list[$key]['update'] ){
                     $list[$key]['grade_coin'] = D("member")->updatedata($value['user_id'],$list[$key]['update'] );
                 }*/
                /*if($update <= $list[$key]['totalcoin']){
                    $updatees = [
                        "grade_coin" => $list[$key]['totalcoin'],
                    ];
                    $list[$key]['grade_coin'] = D("member")->updatedata($value['user_id'],$updatees);
                }else{
                    $list[$key]['grade_coin'] = D("member")->updatedata($value['user_id'],$update);
                }*/
            }

            //查询成功
            $this -> returnCode = 200;
            $this -> returnData = $list;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
    }
}


?>