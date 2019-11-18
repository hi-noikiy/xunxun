<?php
namespace Admin\Controller;
use Admin\Service\AdminService;
use Common\Util\ParamCheck;
use Think\Controller;
use Think\Exception;
use Think\Log;

class CountDataesController extends BaseController{

    /**当前天数据统计
     * @param string  $todaytime 当天时间
     * @param string  $endtime 结束时间
     * @param string  $token  token值
     * @param string  $page 页数
     */
    public function countdataes(){
        //接口值
        $data = [
            "todaytime" => I("get.todaytime"),
            "endtime" => I("get.endtime"),
            "page" => I("get.page"),
        ];
        try{
            $list = [];
            // 每页条数
            $size = 20;
            $pageNum = empty($size)?20:$size;
            $page = empty($data['page'])?1:$data['page'];
            if($data['todaytime'] && $data['endtime']){ //搜索功能
                $day_begindate = $data['todaytime'];       //今天起始时间
                $day_enddate = $data['endtime'];       //今天结束时间
                $where = array(
                    "todaytime >= '".$day_begindate."' and todaytime <= '".$day_enddate."'",
                );
                $listes = M("todaycount")->where($where)->select();
                if(empty($listes)){
                    $listes = [];
                }
//                echo M("todaycount")->getLastSql();die();
            }else{
                $list["todaytime"] = date('Y-m-d',time());
                $data['todaytime'] = date("Y-m-d",time());
                $data['endtime'] = date("Y-m-d",time());
                $Android = "5ce644560cafb24e1d0000d5";
                $ios = "5d26e004570df31376000c35";
                $umeng = new ComController();
//            print_r($data);die();
                //安卓appkey统计
                $umeng_android = $umeng->comdata($Android,$data['todaytime'],$data['endtime']);
                $umeng_android = explode(',',$umeng_android);
//            print_r($umeng_android);die();
                //苹果appkey统计
                $umeng_ios = $umeng->comdata($ios,$data['todaytime'],$data['endtime']);
                $umeng_ios = explode(',',$umeng_ios);
//            print_r($umeng_ios);die();
                $list['todaytime'] = date('Y-m-d',time());
                $day_begindate = date('Y-m-d 00:00:00');       //今天起始时间
                $day_enddate = date('Y-m-d 23:59:59');                              //今天结束时间
                $where = array(
                    "register_time >= '".$day_begindate."' and register_time <= '".$day_enddate."'",
                );
            var_dump($umeng_android);
            var_dump($umeng_ios);die();
                //$list['newmember'] = M("member")->where($where)->count();       //新增用户统计数据库
                $list['newmember']  = $umeng_android[1] + $umeng_ios[1];
//                echo M("member")->getLastSql();die();
                //获取新增用户id
                $member_list = M("member")->field('id,username')->where($where)->select();
                $string = "";
                foreach($member_list as $key=>$value){
                    $string .= ','.$value['id'];
                }
                //次留
                $list['retention'] = $umeng_android[0] + $umeng_ios[0];
                //日活
                $list['nikkatsu'] = "";
                //平均使用时长
                $list['average_time'] = "";
                $stringUid = trim($string, ",");
                //2.充值金额(当前天的充值总和所有用户)
                $charewhere = array(
                    "addtime >= '".$day_begindate."' and addtime <= '".$day_enddate."'",
                    'status'=> '1',
                );
                $totalrmb = M("chargedetail")->field('rmb')->where($charewhere)->sum("rmb");
//            echo M("chargedetail")->getLastSql();die();
                $list['totalrmb'] = $totalrmb?$totalrmb:"0.00";
                //3.付费人数
                $paynumber = M("chargedetail")->field('rmb')->where($charewhere)->count('Distinct uid');
//            echo M("chargedetail")->getLastSql();die();
                $list['paynumber'] = $paynumber?$paynumber:"0";
                //4.付费率(新增付费人数/新增人数)
                $list['payrate'] = floor($list['paynumber']/$list['newmember']*100)."%";
                //5.平均付费(充值金额/新曾用户去重)
                $list['paymean'] = number_format($list['totalrmb']/$list['newmember'],2);
                //6.付费用户平均付费(当日所有充值总和与当日所有充值总人数的比例值)
                $wheretime = array(
                    "addtime >= '".$day_begindate."' and addtime <= '".$day_enddate."'",
                    'status'=> '1',
                );
                $todaypayrmb = M("chargedetail")->field('rmb')->where($wheretime)->sum("rmb");          //当日所有充值总和
                $todaypaynumber = M("chargedetail")->field('rmb')->where($wheretime)->count('Distinct uid');          //当日所有充值人数总和
//            echo M("chargedetail")->getLastSql();die();
                $list['todaypaymean'] = number_format($todaypayrmb/$todaypaynumber,2);
                //7.M豆消耗
                $freewhere = array(
                    "addtime >= '".$day_begindate."' and addtime <= '".$day_enddate."'",
//                "uid"=> array("in",$stringUid),
                    'action'=>array("in", 'sendgift,sendgiftFromBag,BreakEgg'),
                );
                $freecoin = M("coindetail")->field("coin")->where($freewhere)->sum("coin");
                $list['freecoin'] = $freecoin?$freecoin:"0";
                //8.兑换钻石消耗
                $changewhere = array(
                    "addtime >= '".$day_begindate."' and addtime <= '".$day_enddate."'",
                    "uid"=> array("in",$stringUid),
                    'action'=> 'changes',
                );
                $changecoin = M("coindetail")->field("coin")->where($changewhere)->sum("coin");
                $list['changecoin'] = $changecoin?$changecoin:"0";
                //9.剩余M豆(全平台剩余的M豆)
                $alltotalcoin =  M("member")->field("totalcoin")->sum("totalcoin");
                $allfreecoin = M("member")->field("freecoin")->sum("freecoin");
                //还有砸蛋礼物背包数据
                //统计所有背包礼物数据以礼物id
                $sum = 0;
                $packnumber = M("pack")->alias('p')->field('sum(pack_num) as pack_num,gift_id,g.gift_coin,sum(pack_num*g.gift_coin) as totalnum')->join('left join zb_gift g on id=p.gift_id')->group('gift_id')->select();
                foreach($packnumber as $key=>$value){
                    $sum += $value['totalnum'];
                }
//            echo $sum;die();
//            echo M("pack")->getLastSql();die();
                $list['remanentcoin'] = $alltotalcoin - $allfreecoin + $sum;
                //10.剩余钻石
                //用户钻石统计
                $diamond =  M("member")->field("diamond")->sum("diamond");
                $exchange_diamond = M("member")->field("exchange_diamond")->sum("exchange_diamond");
                $free_diamond = M("member")->field("exchange_diamond")->sum("free_diamond");
                $member_remanent = $diamond - $exchange_diamond - $free_diamond;
                //公会钻石统计
                $guild_diamond = M("member_guild")->field("diamond")->sum("diamond");
                $guild_free_diamond = M("member_guild")->field("free_diamond")->sum("free_diamond");
                $guild_remanent = $guild_diamond - $guild_free_diamond;
                $list['remanentdiamond'] = $member_remanent + $guild_remanent;
               /* //1.统计当前今天的注册人数
                $day_begindate = date('Y-m-d 00:00:00');       //今天起始时间
                $day_enddate = date('Y-m-d 23:59:59');                              //今天结束时间
                $where = array(
                    "register_time >= '".$day_begindate."' and register_time <= '".$day_enddate."'",
                );
                $list['newmember'] = M("member")->where($where)->count();       //新增用户统计
//                echo M("member")->getLastSql();die();
                //获取新增用户id
                $member_list = M("member")->field('id,username')->where($where)->select();
                $string = "";
                foreach($member_list as $key=>$value){
                    $string .= ','.$value['id'];
                }
                //次留
                $list['retention'] = "";
                //日活
                $list['nikkatsu'] = "";
                //平均使用时长
                $list['average_time'] = "";
                $stringUid = trim($string, ",");
                //2.充值金额(当前天的充值总和)
                $charewhere['uid'] = array("in",$stringUid);
                $charewhere['status'] = 1;
                $charewhere = array(
                    "addtime >= '".$day_begindate."' and addtime <= '".$day_enddate."'",
                    "uid"=> array("in",$stringUid),
                    'status'=> '1',
                );
                $totalrmb = M("chargedetail")->field('rmb')->where($charewhere)->sum("rmb");
//            echo M("chargedetail")->getLastSql();die();
                $list['totalrmb'] = $totalrmb?$totalrmb:"0.00";
                //3.付费人数
                $paynumber = M("chargedetail")->field('rmb')->where($charewhere)->count('Distinct uid');
//            echo M("chargedetail")->getLastSql();die();
                $list['paynumber'] = $paynumber?$paynumber:"0";
                //4.付费率(新增人数/新增付费人数)
                $list['payrate'] = round($list['paynumber']/$list['newmember']*100)."%";
                //5.平均付费
                $list['paymean'] = number_format($list['totalrmb']/$list['paynumber'],2);
                //6.付费用户平均付费(当日所有充值总和与当日所有充值总人数的比例值)
                $wheretime = array(
                    "addtime >= '".$day_begindate."' and addtime <= '".$day_enddate."'",
                    'status'=> '1',
                );
                $todaypayrmb = M("chargedetail")->field('rmb')->where($wheretime)->sum("rmb");          //当日所有充值总和
                $todaypaynumber = M("chargedetail")->field('rmb')->where($wheretime)->count('Distinct uid');          //当日所有充值人数总和
//            echo M("chargedetail")->getLastSql();die();
                $list['todaypaymean'] = number_format($todaypayrmb/$todaypaynumber,2);
                //7.M豆消耗
                $freewhere = array(
                    "addtime >= '".$day_begindate."' and addtime <= '".$day_enddate."'",
                    "uid"=> array("in",$stringUid),
                    'action'=> 'sendgift',
                );
                $freecoin = M("coindetail")->field("coin")->where($freewhere)->sum("coin");
//            echo M("coindetail")->getLastSql();die();
                $list['freecoin'] = $freecoin?$freecoin:"0";
                //8.兑换钻石消耗
                $changewhere = array(
                    "addtime >= '".$day_begindate."' and addtime <= '".$day_enddate."'",
                    "uid"=> array("in",$stringUid),
                    'action'=> 'changes',
                );
                $changecoin = M("coindetail")->field("coin")->where($changewhere)->sum("coin");
                $list['changecoin'] = $changecoin?$changecoin:"0";
                //9.剩余M豆(全平台剩余的M豆)
                $alltotalcoin =  M("member")->field("totalcoin")->sum("totalcoin");
                $allfreecoin = M("member")->field("freecoin")->sum("freecoin");
                $list['remanentcoin'] = $alltotalcoin - $allfreecoin;
                //10.剩余钻石
                //用户钻石统计
                $diamond =  M("member")->field("diamond")->sum("diamond");
                $exchange_diamond = M("member")->field("exchange_diamond")->sum("exchange_diamond");
                $free_diamond = M("member")->field("exchange_diamond")->sum("free_diamond");
                $member_remanent = $diamond - $exchange_diamond - $free_diamond;
                //公会钻石统计
                $guild_diamond = M("member_guild")->field("diamond")->sum("diamond");
                $guild_free_diamond = M("member_guild")->field("free_diamond")->sum("free_diamond");
                $guild_remanent = $guild_diamond - $guild_free_diamond;
                $list['remanentdiamond'] = $member_remanent - $guild_remanent;*/
                //将数据插入数据库中
                if($list['todaytime'] !== date("Y-m-d",time())){
                    $insert_data =  [
                        "todaytime" => $list['todaytime'],      //时间
                        "newmember" => $list['newmember'],      //新增用户
                        "retention" => $list['retention'],      //次留
                        "nikkatsu" => $list['nikkatsu'],        //日活
                        "average_time" => $list['average_time'],        //平均使用时长
                        "totalrmb" => $list['totalrmb'],            //充值
                        "paynumber" => $list['paynumber'],          //付费人数
                        "payrate" => $list['payrate'],              //付费率
                        "paymean" => $list['paymean'],              //平均付费
                        "todaypaymean" => $list['todaypaymean'],        //付费用户平均付费
                        "freecoin" => $list['freecoin'],                //M豆消耗
                        "changecoin" => $list['changecoin'],            //兑换钻石消耗
                        "remanentcoin" => $list['remanentcoin'],            //剩余M豆
                        "remanentdiamond" => $list['remanentdiamond'],      //剩余钻石
                    ];
                    M("todaycount")->add($insert_data);
                }else{
                    $count = M("todaycount")->count();       //统计所有的值
                    $totalPage = ceil($count/$pageNum);
                    $limit = ($page-1) * $size . "," . $size;
                    $listes = M("todaycount")->limit($limit)->order('todaytime desc')->select();
//                    echo M("todaycount")->getLastSql();die();
                    if($page<= $totalPage){
                        $listes[] = $list;      //将今日数据压入列表中并且排序
                        foreach($listes as $key=>$value){
                            $todaytime[] = $value['todaytime'];
                        }
                        array_multisort($todaytime,SORT_DESC,$listes);
                    }else{
                        $listes = [];
                    }
//                    echo M("todaycount")->getLastSql();die();
                }
            }
            $pageInfo = array("page" => $page, "pageNum"=>$pageNum, "totalPage" => $totalPage);
            $result = [
                "list" => $listes,
                "pageinfo" => $pageInfo,
            ];
            $this -> returnCode = 200;
            $this -> returnData = $result;
        }catch (\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();
    }

    //脚本统计数据
    /**脚本统计数据
     * @param string  $todaytime 当天时间
     * @param string  $endtime 结束时间
     * @param string  $token  token值
     * @param string  $page 页数
     */
    public function daycountes(){
        //获取数据
        $data = [
            "todaytime" => I("get.todaytime"),
            "endtime" => I("get.endtime"),
        ];
        try{
            //1.统计当前今天的注册人数跑的数据
//            $day_begindate = date('Y-m-d',time());       //今天起始时间
//            $day_enddate = date('Y-m-d',time());         //今天结束时间
//            $day_begindate = date('2019-08-25');       //模拟今天起始时间
//            $day_enddate = date('2019-08-25');         //模拟今天结束时间
//            $data['todaytime'] = $day_begindate;
//            $data['endtime'] = $day_enddate;
            $Android = "5ce644560cafb24e1d0000d5";
            $ios = "5d26e004570df31376000c35";
            $umeng = new ComController();
//            print_r($umeng);die();
            //安卓appkey统计
            $umeng_android = $umeng->comdata($Android,$data['todaytime'],$data['endtime']);
            $umeng_android = explode(',',$umeng_android);
//            print_r($umeng_android);die();
            //苹果appkey统计
            $umeng_ios = $umeng->comdata($ios,$data['todaytime'],$data['endtime']);
            $umeng_ios = explode(',',$umeng_ios);
//            print_r($umeng_ios);die();
            //当天脚本数据跑的数据
            /*$list['todaytime'] = date('Y-m-d',time());
            $day_begindate = date('Y-m-d 00:00:00');       //今天起始时间
            $day_enddate = date('Y-m-d 23:59:59');                              //今天结束时间*/
            //查询数据
            $list['todaytime'] = $data['todaytime'];
            $day_begindate = $data['todaytime']." 00:00:00";       //今天起始时间
            $day_enddate = $data['endtime']." 23:59:59";                              //今天结束时间

            $where = array(
                "register_time >= '".$day_begindate."' and register_time <= '".$day_enddate."'",
            );
//            var_dump($where);die();
//            var_dump($umeng_android);
//            var_dump($umeng_ios);die();
            //$list['newmember'] = M("member")->where($where)->count();       //新增用户统计数据库
            $list['newmember']  = $umeng_android[1] + $umeng_ios[1];
//                echo M("member")->getLastSql();die();
            //获取新增用户id
            $member_list = M("member")->field('id,username')->where($where)->select();
            $string = "";
            foreach($member_list as $key=>$value){
                $string .= ','.$value['id'];
            }
            //次留
            $list['retention'] = $umeng_android[0] + $umeng_ios[0];
            //日活
            $list['nikkatsu'] = "0";
            //平均使用时长
            $list['average_time'] = "0";
            $stringUid = trim($string, ",");
            //2.充值金额(当前天的充值总和所有用户)
            $charewhere = array(
                "addtime >= '".$day_begindate."' and addtime <= '".$day_enddate."'",
                'status'=> '1',
            );
            $totalrmb = M("chargedetail")->field('rmb')->where($charewhere)->sum("rmb");
//            echo M("chargedetail")->getLastSql();die();
            $list['totalrmb'] = $totalrmb?$totalrmb:"0.00";
            //3.付费人数
            $paynumber = M("chargedetail")->field('rmb')->where($charewhere)->count('Distinct uid');
//            echo M("chargedetail")->getLastSql();die();
            $list['paynumber'] = $paynumber?$paynumber:"0";
            //4.付费率(新增付费人数/新增人数)
            $list['payrate'] = floor($list['paynumber']/$list['newmember']*100)."%";
            //5.平均付费(充值金额/新曾用户去重)
            $list['paymean'] = number_format($list['totalrmb']/$list['newmember'],2);
            //6.付费用户平均付费(当日所有充值总和与当日所有充值总人数的比例值)
            $wheretime = array(
                "addtime >= '".$day_begindate."' and addtime <= '".$day_enddate."'",
                'status'=> '1',
            );
            $todaypayrmb = M("chargedetail")->field('rmb')->where($wheretime)->sum("rmb");          //当日所有充值总和
            $todaypaynumber = M("chargedetail")->field('rmb')->where($wheretime)->count('Distinct uid');          //当日所有充值人数总和
//            echo M("chargedetail")->getLastSql();die();
            $list['todaypaymean'] = number_format($todaypayrmb/$todaypaynumber,2);
            //7.M豆消耗
            $freewhere = array(
                "addtime >= '".$day_begindate."' and addtime <= '".$day_enddate."'",
//                "uid"=> array("in",$stringUid),
                'action'=>array("in", 'sendgift,sendgiftFromBag,BreakEgg'),
            );
            $freecoin = M("coindetail")->field("coin")->where($freewhere)->sum("coin");
            $list['freecoin'] = $freecoin?$freecoin:"0";
            //8.兑换钻石消耗
            $changewhere = array(
                "addtime >= '".$day_begindate."' and addtime <= '".$day_enddate."'",
                "uid"=> array("in",$stringUid),
                'action'=> 'changes',
            );
            $changecoin = M("coindetail")->field("coin")->where($changewhere)->sum("coin");
            $list['changecoin'] = $changecoin?$changecoin:"0";
            //9.剩余M豆(全平台剩余的M豆)
            $alltotalcoin =  M("member")->field("totalcoin")->sum("totalcoin");
            $allfreecoin = M("member")->field("freecoin")->sum("freecoin");
            //还有砸蛋礼物背包数据
            //统计所有背包礼物数据以礼物id
            $sum = 0;
            $packnumber = M("pack")->alias('p')->field('sum(pack_num) as pack_num,gift_id,g.gift_coin,sum(pack_num*g.gift_coin) as totalnum')->join('left join zb_gift g on id=p.gift_id')->group('gift_id')->select();
            foreach($packnumber as $key=>$value){
                $sum += $value['totalnum'];
            }
//            echo $sum;die();
//            echo M("pack")->getLastSql();die();
            $list['remanentcoin'] = $alltotalcoin - $allfreecoin + $sum;
            //10.剩余钻石
            //用户钻石统计
            $diamond =  M("member")->field("diamond")->sum("diamond");
            $exchange_diamond = M("member")->field("exchange_diamond")->sum("exchange_diamond");
            $free_diamond = M("member")->field("exchange_diamond")->sum("free_diamond");
            $member_remanent = $diamond - $exchange_diamond - $free_diamond;
            //公会钻石统计
            $guild_diamond = M("member_guild")->field("diamond")->sum("diamond");
            $guild_free_diamond = M("member_guild")->field("free_diamond")->sum("free_diamond");
            $guild_remanent = $guild_diamond - $guild_free_diamond;
            $list['remanentdiamond'] = $member_remanent + $guild_remanent;
//            var_dump($list);die();
            //将数据插入数据库中
            $insert_data =  [
                "todaytime" => $list['todaytime'],      //时间
                "newmember" => $list['newmember'],      //新增用户
                "retention" => $list['retention'],      //次留
                "nikkatsu" => $list['nikkatsu'],        //日活
                "average_time" => $list['average_time'],        //平均使用时长
                "totalrmb" => $list['totalrmb'],            //充值
                "paynumber" => $list['paynumber'],          //付费人数
                "payrate" => $list['payrate'],              //付费率
                "paymean" => $list['paymean'],              //平均付费
                "todaypaymean" => $list['todaypaymean'],        //付费用户平均付费
                "freecoin" => $list['freecoin'],                //M豆消耗
                "changecoin" => $list['changecoin'],            //兑换钻石消耗
                "remanentcoin" => $list['remanentcoin'],            //剩余M豆
                "remanentdiamond" => $list['remanentdiamond'],      //剩余钻石
            ];
            M("todaycount")->add($insert_data);
            $this -> returnCode = 200;
        }catch (\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();
    }

    /**统计周报接口
     * @param string  $token  token值
     * @param string  $page 页数
     */
    public function weekdataes(){
        //获取数据
        $data = [
            "token" => I("get.token"),
            "page" => I("get.page"),
        ];
        try{
            //校验数据
            if($data['page']){
                ParamCheck::checkInt("page",$data['page'],1);
            }else{
                $data['page'] = 1;
            }
            //查询数据(统计上周的数据)
            $beginLastweek = mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));
            $endLastweek = mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'));
            //0.当前上一周时间
            $list['weektime'] = date('Y-m-d',$beginLastweek)."-".date('Y-m-d',$endLastweek);
            $beginLastweek = date('Y-m-d',$beginLastweek)." 00:00:00";
            $endLastweek = date('Y-m-d',$endLastweek)." 23:59:59";
            //1.统计新增用户
            $where = array(
                "register_time >= '".$beginLastweek."' and register_time <= '".$endLastweek."'",
            );
            $list['newmember'] = M("member")->where($where)->count();       //新增用户统计
            var_dump($list);die();
            //获取新增用户id
            $member_list = M("member")->field('id,username')->where($where)->select();
            $string = "";
            foreach($member_list as $key=>$value){
                $string .= ','.$value['id'];
            }
            $stringUid = trim($string, ",");
            var_dump($stringUid);die();
            echo M("member")->getLastSql();die();
            var_dump($beginLastweek);
            echo "<br>";
            var_dump($endLastweek);die();
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
