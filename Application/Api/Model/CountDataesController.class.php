<?php
namespace Api\Controller;
use Api\Service\MemberService;
use Think\Controller;
use Think\Exception;
use Think\Log;
class CountDataesController extends Controller{

    /**当前天数据统计
     * @param $todaytime        当天时间
     */
    public function countdataes($todaytime){
        //接口值
        $data = [
            "todaytime" => I("get.todaytime"),
        ];
        try{
            $list = [];
            //1.统计当前今天的注册人数
            $where['register_time'] =
            $day_begindate=date('Y-m-d 00:00:00');       //今天起始时间
            $day_enddate=date('Y-m-d 23:59:59');                              //今天结束时间
            $where = array(
                "register_time >= '".$day_begindate."' and register_time <= '".$day_enddate."'",
            );
            $list['newmember'] = M("member")->where($where)->count();       //新增用户统计
            //获取新增用户id
            $member_list = M("member")->field('id,username')->where($where)->select();
            $string = "";
            foreach($member_list as $key=>$value){
                $string .= ','.$value['id'];
            }
            $newstring = trim($string, ",");
            echo $newstring;die();
            //2.充值金额
            //3.付费人数
            //4.付费率
            //5.平均付费
            //6.付费用户平均付费
            //7.M豆消耗
            //8.兑换钻石消耗
            //9.剩余M豆
            //10.剩余钻石
            $result = [
                "pageInfo"=> $list,
            ];
            $this -> returnCode = 200;
            $this -> returnData = $result;
        }catch (\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
    }


}
