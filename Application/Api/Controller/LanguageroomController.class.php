<?php

namespace Api\Controller;

use Api\Service\LanguageroomService;
use Api\Service\MemberService;
use Api\Service\RoomtotalsService;
use Api\Service\RoomMemberService;
use Api\Service\DebarService;
use Api\Service\RoomManagerService;
use Api\Service\MonitoringService;
use Api\Service\TagsService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\emchat\Easemob;
use Think\Exception;
use Think\Log;

class LanguageroomController extends BaseController {

    /**首页接口
     * 参数说明:请求方式以get
     * @param $token    token值
     * @param $signature    验签md5(token+room_type)
     * 返回值说明:对应所有房间类型数据
     * local.yd.com/index.php/Api/Languageroom/getRoomList?token=23f376d40e9a98a5046ce3a2b77acce6&room_type=1&page=1
     */
    public function RoomList($token,$signature=null){
        //获取数据
        $data = [
            "token"=>I('post.token'),
            "signature"=>I('post.signature'),
        ];
        try{
            //校验签名
            /*if($data['signature'] !== md5(strtolower($data['token']))){
                E("验签失败",2000);
            }*/
            //所有房间分类 热门,女神,型男,交友,个人
            $room_mode = D('RoomMode')->getListes();
            $item = [];
            foreach($room_mode as $k=>$v){
//                var_dump($v);
//                $item[$v['room_mode']]['id'] = $v['mode_id'];
//                var_dump($item[$v['room_mode']]);
                $item[$v['mode_id']] = $v['mode_id'];
                if($v['mode_id'] == 1){
                    //热门数据
                    $list = LanguageroomService::getInstance()->getRedList($limit=10);
                    if($list){
                        foreach($list as $key=>$value){
                            $list[$key]['room_image'] = MemberService::getInstance()->getOneByIdField($value['user_id'],"avatar");//用户头像
                            if(empty($list[$key]['room_image'])){
                                $list[$key]['room_image'] = C('APP_URL').'/Public/Uploads/image/logo.png';
                            }else{
                                $list[$key]['room_image'] = C('APP_URL').MemberService::getInstance()->getOneByIdField($value['user_id'],"avatar");//用户头像
                            }
                            $list[$key]['nickname'] = MemberService::getInstance()->getOneByIdField($value['user_id'],"nickname");  //用户昵称
                            $list[$key]['room_type'] = D('RoomMode')->getOneByIdField($value['room_type'],"room_mode");         //房间类型
//                            $list[$key]['room_tags'] = TagsService::getInstance()->getOneByIdField($value['room_tags'],"tag_name");  //标签
//                            $list[$key]['room_tags'] = D('RoomMode')->getOneByIdField($value['room_type'],"room_mode");  //房间标签
                            $list[$key]['room_tags'] = D('RoomMode')->getOneByIdField($value['room_type'],"room_mode");  //房间标签
                            if(empty($list[$key]['room_tags'])){
                                $sex = MemberService::getInstance()->getOneByIdField($value['user_id'],"sex");  //用户性别(如果当前房间没有标签那么跟着当前创始人用户的性别标签)
                                if($sex ==1){
                                    $list[$key]['room_tags'] = "男神";
                                }else if($sex ==2){
                                    $list[$key]['room_tags'] = "女神";
                                }else if($sex ==3){
                                    $list[$key]['room_tags'] = "保密";
                                }
                            }
                            unset($list[$key]['user_id']);
                            $list[$key] = dealnull($list[$key]);
                        }
                    }else{
                        $list = [];
                    }
                }else{
                    $list = LanguageroomService::getInstance()->getBytypeList($limit=10,$v['mode_id']);
                    if($list){
                        foreach($list as $key=>$value){
                            $list[$key]['room_image'] = MemberService::getInstance()->getOneByIdField($value['user_id'],"avatar");//用户头像
                            if(empty($list[$key]['room_image'])){
                                $list[$key]['room_image'] = C('APP_URL').'/Public/Uploads/image/logo.png';
                            }else{
                                $list[$key]['room_image'] = C('APP_URL').MemberService::getInstance()->getOneByIdField($value['user_id'],"avatar");//用户头像
                            }
                            $list[$key]['nickname'] = MemberService::getInstance()->getOneByIdField($value['user_id'],"nickname");  //用户昵称
                            $list[$key]['room_type'] = D('RoomMode')->getOneByIdField($value['room_type'],"room_mode");         //房间类型
//                            $list[$key]['room_tags'] = TagsService::getInstance()->getOneByIdField($value['room_tags'],"tag_name");  //用户昵称
                            $list[$key]['room_tags'] = D('RoomMode')->getOneByIdField($value['room_type'],"room_mode");  //房间标签
                            if(empty($list[$key]['room_tags'])){
                                $sex = MemberService::getInstance()->getOneByIdField($value['user_id'],"sex");  //用户性别(如果当前房间没有标签那么跟着当前创始人用户的性别标签)
                                if($sex ==1){
                                    $list[$key]['room_tags'] = "男神";
                                }else if($sex ==2){
                                    $list[$key]['room_tags'] = "女神";
                                }else if($sex ==3){
                                    $list[$key]['room_tags'] = "保密";
                                }
                            }
                            unset($list[$key]['user_id']);
                            $list[$key] = dealnull($list[$key]);
                        }
                    }else{
                        $list = [];
                    }
                }
//                $item[$v['room_mode']] = $list;
                $item[$v['mode_id']] = $list;

            }
            $result = [
                "room_list"=>$item,
            ];
            $this->returnCode = 200;
            $this->returnData = $result;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }

    /**首页接口
     * 参数说明:请求方式以get
     * @param $token    token值
     * @param $room_type 类型值 1热门 2电台 3陪玩 4虚拟朋友 5派单 6交友创建
     * @param $page   分页
     * @param $signature    验签md5(token+room_type)
     * 返回值说明:轮播与对应的语言列表
     * local.yd.com/index.php/Api/Languageroom/getRoomList?token=23f376d40e9a98a5046ce3a2b77acce6&room_type=1&page=1
     */
    public function getRoomList($token,$room_type,$page=1,$signature){
        //获取数据
        $data = [
            "token"=>I('post.token'),
            "room_type"=> I('post.room_type'),
            "signature"=>I('post.signature'),
        ];
        try{
            //校验签名
//            if($data['signature'] !== md5(strtolower($data['token'].$data['room_type']))){
//                E("验签失败",2000);
//            }
            //校验数据
            ParamCheck::checkInt("room_type",$data['room_type'],1);
            ParamCheck::checkInt("page",$page,1);
            //房间分类
            $status = 2;    //此把推荐的去掉
            $room_mode = D('RoomMode')->getList($status);
//            $room_mode = D('RoomMode')->getListes();
            // 每页条数
            $size = 20;
            $pageNum = empty($size)?20:$size;
            $page = empty($page)?1:$page;
            //获取语言列表数据
            $banben = explode(',', $this->clientVersion);
            if($data['room_type'] == 1){
                //判断版本不显示电台
                $diantai = false;
                if (version_compare($banben[0], '2.2.0', '<')) {
                    $diantai = true;
                }
                //热门数据统计
                $count =LanguageroomService::getInstance()->countered();
                // 总页数.
                $totalPage = ceil($count/$pageNum);
                $pageInfo = array("page" => $page, "pageNum"=>$pageNum, "totalPage" => $totalPage);
                $limit = ($page-1) * $size . "," . $size;
                $list = LanguageroomService::getInstance()->getRedList($limit,$diantai);
            }else{
                //判断版本不显示电台
                if (version_compare($banben[0], '2.2.0', '<')) {
                    // 总页数.
                    $totalPage = 0;
                    // 页数信息.
                    $pageInfo = array("page" => $page, "pageNum"=>$pageNum, "totalPage" => $totalPage);
                    $limit = ($page-1) * $size . "," . $size;
                    $list = [];
                }else{
                    $where['room_type'] = $data['room_type'];
                    $count =LanguageroomService::getInstance()->countes($where['room_type']);
                    // 总页数.
                    $totalPage = ceil($count/$pageNum);
                    // 页数信息.
                    $pageInfo = array("page" => $page, "pageNum"=>$pageNum, "totalPage" => $totalPage);
                    $limit = ($page-1) * $size . "," . $size;
    //                $list = RoomtotalsService::getInstance()->getBytypeList($limit,$data['room_type']);
                    $list = LanguageroomService::getInstance()->getBytypeList($limit,$data['room_type']);
                }
                
            }
            if($list){
                foreach($list as $key=>$value){
                    $list[$key]['visitor_number'] = $value['visitor_number']+$value['visitor_externnumber']+$value['visitor_users'];//房间热度值
                    if(empty($list[$key]['visitor_number'])){
                        $list[$key]['visitor_number'] = "0";
                    }
                    $list[$key]['room_image'] = getavatar(MemberService::getInstance()->getOneByIdField($value['user_id'],"avatar"));//用户头像
//                    if(empty($list[$key]['room_image'])){
//                        $list[$key]['room_image'] = C('APP_URL').'/Public/Uploads/image/logo.png';
//                    }else{
//                        $list[$key]['room_image'] = C('APP_URL').MemberService::getInstance()->getOneByIdField($value['user_id'],"avatar");//用户头像
//                    }
                    $list[$key]['nickname'] = MemberService::getInstance()->getOneByIdField($value['user_id'],"nickname");
                    $list[$key]['room_type'] = D('RoomMode')->getOneByIdField($value['room_type'],"room_mode");         //房间类型
//                        $list[$key]['room_tags'] = TagsService::getInstance()->getOneByIdField($value['room_tags'],"tag_name");  //用户昵称
                    $list[$key]['room_tags'] = D('RoomMode')->getOneByIdField($value['room_type'],"room_mode");   //房间类型
                    if(empty($list[$key]['room_tags'])){
                        $sex = MemberService::getInstance()->getOneByIdField($value['user_id'],"sex");  //用户性别(如果当前房间没有标签那么跟着当前创始人用户的性别标签)
                        if($sex ==1){
                            $list[$key]['room_tags'] = "男神";
                        }else if($sex ==2){
                            $list[$key]['room_tags'] = "女神";
                        }else if($sex ==3){
                            $list[$key]['room_tags'] = "保密";
                        }
                    }
                    unset($list[$key]['user_id']);
                    unset($list[$key]['visitor_externnumber']);
                    unset($list[$key]['visitor_users']);
                    $list[$key] = dealnull($list[$key]);
                }
            }else{
                $list = [];
            }
            //返回数据
            $result = [
                "room_mode" => $room_mode,
                "room_list"=>$list,
                "pageInfo"=>$pageInfo,
            ];
            $this->returnCode = 200;
            $this->returnData = $result;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }

    /**
     * 房间基本信息值
     * @param $token    token值
     * @param $room_id  房间id
     * @param $signature    验签md5(token+room_id)
     * http://local.yd.com/index.php/Api/Languageroom/getRoomDetail?token=b226c0bd538fa1c76e22100e0706d27b&room_id=3
     */
    public function getRoomDetail($token,$room_id,$signature){
        //获取数据
        $data = [
            "token"=>I('get.token'),
            "room_id"=>I('get.room_id'),
            "signature"=>I('get.signature'),
        ];
        $user_info['id'] = RedisCache::getInstance()->get($data['token']);    //用户id
        try{
            //校验数据
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            //校验签名
            if($data['signature'] !== md5(strtolower($data['token'].$data['room_id']))){
                E("验签失败",2000);
            }
            //当前房间id是否存在
            $is_roomid= LanguageroomService::getInstance() -> getDeatil($data['room_id']);
            if(empty($is_roomid['room_id'])){
                E("该当前房间不存在",2000);
            }
            //当前用户是否禁言0未禁言 1禁言
            $is_speak = 0;
            //当前用户是否被禁入(主持人与管理员可以进来)
            $debardata = [
                "user_id" => $user_info['id'],
                "room_id" => $data['room_id'],
                "type" => 1,
            ];
            $is_debar = DebarService::getInstance()->isDebar($debardata);
            if($is_debar){
                E("您被禁入此房间,请与房主联系",2000);
            }
            //当前房间是否被锁定状态(主持人与管理员可以进来,其他用户进不来)todo重构
            if($is_roomid['room_lock'] ==1){
                //查询该用户是否在管理员列表中存在
                $isRoomManager = RoomManagerService::getInstance()->isRoomManager($data['room_id'],$user_info['id']);
                if($user_info['id'] !==$is_roomid['user_id'] && $user_info['id'] !== $isRoomManager['user_id']){
                    E("该房间被锁了",2000);
                }
            }
            /*if($user_info['id'] !== $is_roomid['user_id'] && $is_roomid['room_lock'] == 1){ //主持人
                E("该房间被锁",2000);
            }*/
            //增加访问人数值
            D('Roomtotals')->setInces($data['room_id']);
            ////增加该房间内在线用户列表(在他的个人中心有是否在某个房间里)
            $memberdata = [
                "rooms_id" => $data['room_id'],
                "user_id" => $user_info['id'],
                "creattime" => time(),
            ];
            RoomMemberService::getInstance()->addData($memberdata);
            //查询当前房间的数据
            $room_info = LanguageroomService::getInstance() -> getDeatil($data['room_id']);
            $room_info['room_images'] =C('APP_URL'). $room_info['room_image'];
            $room_info['is_speak'] = $is_speak;     //此用户是否禁言
            //当前用户进来房间后的状态(普通用户还是管理员还是主持人)
            $room_info['room_user_status'] = 2;     //1普通用户 2管理员 3主持人
            $room_info['is_freemai'] = 2;     //1非自由麦 2自由麦
            $room_info['is_richesnumber'] = 1;     //1心动值开启 2心动值关闭
            $room_info = dealnull($room_info);
            //当前用户当日收到礼物前三名头像(需要重构)
            // $avatar =C('APP_URL'). MemberService::getInstance()->getOneByIdField($user_info['id'],"avatar");
            // for ($x=0; $x<=2; $x++) {
            //     $riches_member[] = $avatar;
            // }
            //获取轮播图数据
            $type = 2;  //轮播类型1首页 2房间
            $banner_list = D('banner')->getAppUrl($type);
            if($banner_list==null){
                $banner_list = [];
            }


            //连麦用户数据(头像与当前房间内的心动值)模拟数据
            $type = 1;  //轮播类型1首页 2房间
            $lianmai_data =[];
            $lianmai_datas = D('banner')->getAppUrl($type);
            $file="updown/".$room_id.'.txt';
            if(!file_exists($file)){
                for($i=1;$i<9;$i++){
                    // var_dump($value);die;
                    $lianmai_data[$i]['lianmai_sort'] = $i;       //连麦用户在第几号麦上
                    $lianmai_data[$i]['lianmai_uid'] = null;       //连麦用户
                    $lianmai_data[$i]['avatar'] = null;    //用户头像
                    $lianmai_data[$i]['riches_number'] = "0";  //心动值
                    $lianmai_data[$i]['lianmai_name'] = null;  //用户昵称
                }
                //主持人数据
                $maizhu =[
                    "maizhu_uid" => "",
                    "maizhu_name" => "",
                    "maizhu_image" =>"",
                    "riches_number" => "0",
                ];

            }else{
                $handle=fopen($file,'r');
                $cacheArray=unserialize(fread($handle,filesize($file)));
                // var_dump($cacheArray);die;
                foreach($cacheArray as $key=>$value){
                    // var_dump($value);die;
                    if($key=="0"){
                        //主持人数据
                        $maizhu =[
                            "maizhu_uid" =>$value[$key]['uid'],
                            "maizhu_name" => $value[$key]['nickname'],
                            "maizhu_image" => $value[$key]['avatar'],
                            "riches_number" => $key,
                        ];
                    }else{
                        $lianmai_data[$key]['lianmai_sort'] = $key;       //连麦用户在第几号麦上
                        $lianmai_data[$key]['lianmai_uid'] = $value[$key]['uid'];       //连麦用户
                        $lianmai_data[$key]['avatar'] = $value[$key]['avatar'];    //用户头像
                        $lianmai_data[$key]['riches_number'] = $value{$key}['maidong'];  //心动值
                        $lianmai_data[$key]['lianmai_name'] = $value[$key]['nickname'];  //用户昵称
                    }

                }

                // var_dump($lianmai_data);die;
            }
            //var_dump($lianmai_data);die;
            unset($room_info['room_image']);        //销毁详情里面room_image替换成room_images;
            foreach($lianmai_data  as $k=>$v){
                $lianmai_data[$k]=dealnull( $lianmai_data[$k]);
            }
            //var_dump($lianmai_data);die;
            $result = [
                "room_info"=>$room_info,    //房间信息
                "maizhu_info"=>dealnull($maizhu),    //房间麦主信息
                "lianmai_data"=>array_values($lianmai_data),          //连麦及心动值
                "riches_member" => $riches_member,    //当前房间内收到礼物日榜前三名用户头像
                "banner_list"=>$banner_list,    //房间轮播
                'hx_room'=>$is_roomid['hx_room']
            ];
            $this -> returnCode = 200;
            $this -> returnData=$result;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this -> returnData();
    }
    /**
     * 创建房间接口
     * 参数说明:请求方式为POST,房间名称在16字以内
     * 如果用户不选择标签默认的走性别标签(男生,女生)
     * @param $token token值
     * @param $room_name 房间名称
     * @param $room_type 房间类型(1.热门,2电台,3陪玩,4虚拟朋友,5.派单,6交友(个人创建))
     * @param $room_image 房间头像
     * @param $room_tag 房间标签
     * @param $device 手机型号
     * @param $version 版本号
     * @param $signature 签名MD5(token+roomtype）
     * 返回值说明:
     */
    public function create($token,$room_name,$room_type,$device=null,$version=null,$room_tag=null,$signature){

        $this -> returnCode = 500;
        $this -> returnMsg = '请升级新版';
        $this -> returnData();

        $token = $_REQUEST['token'];
        $signature = $_REQUEST['post.signature'];
        $user_info['id'] = RedisCache::getInstance()->get($token);    //用户id
        if(empty($user_info['id'])){
            E("该用户不存在",2000);
        }
        //获取数据
        $data = [
            "user_id" => $user_info['id'],
            "room_name" => $_REQUEST['room_name'],
            "room_type"=> $_REQUEST['room_type'],
            "room_tags"=>$_REQUEST['room_tag'],
            "room_createtime"=>time(),
        ];
        try{
            //验证数据
            /*if($signature !== Md5(strtolower($token.$data['room_type']))){
                E("验签失败",2000);
            }*/
            //房间名称不超过16字以内
            $pattern = "/[a-zA-Zxa0-xff_]{0,15}/";
            if( !preg_match($pattern,$data['room_name']) ){
                E("请输入有效的长度名称",2000);
            }
            //检验数据简介
            //内容安全功能接口(房间名称)
            $textcan = new TextcanController();
            $is_safe = $textcan->textcan($data['room_name']);
            if($is_safe !== "pass"){
                E("当前名称包含色情或敏感字字符",2008);
            }
            //判断用户是否为公会或者为普通用户(普通用户只能创建一个房间)
            $role['role'] = MemberService::getInstance()->getOneByIdField($user_info['id'],"role");
            if($role['role'] == 2){ //普通用户
                //查询当前用户是否创建过房间
                $is_room = LanguageroomService::getInstance() -> getFind($user_info['id']);
                if($is_room){
                    E("该用户只能创建一个房间",2000);
                }
            }
            //环信创建房间
            $value = [
                'name'=>$data['room_name'],
                'description'=>'开黑',
                'maxusers'=>5000,
                'owner'=>$user_info['id'],
            ];
            $Easemob = new Easemob();
            $res = $Easemob->createChatRoom($value);
            if(isset($res['error'] )){
                E("创建环信房间异常",2004);
            }else{
                $room_id = $res['data']['id'];
                $data['hx_room'] = $room_id;
                $id = LanguageroomService::getInstance() -> addData($data);
                if(!$id){
                    E("操作失败");
                }else{
                    //修改声网ID为房间号ID
                    $data = ['sw_room'=>$id];
                    $data = ['pretty_room_id'=>$id];
                    $sw = LanguageroomService::getInstance() ->updateDate($id,$data);
                    if(!$sw){
                        E('声网ID未存储成功');
                    }
                    //增加一条人数值统计数据
                    $dataes = [
                        "rooms_id"=>$id,
                        "visitor_number"=>0,
                        "room_types"=>$data['room_type'],
                        "stype"=>1
                    ];
                    $res = RoomtotalsService::getInstance()->addData($dataes);
                    //添加房间麦位数据
                    for($i=1;$i<=9;$i++){
                        $lianmai_data[$i]['room_id'] = $id;       //连麦用户
                        $lianmai_data[$i]['house'] = $i;       //连麦用户在第几号麦上(号位)
                        $lianmai_data[$i]['is_locked'] = 0;    //0 未锁麦 1锁麦
                        $lianmai_data[$i]['is_disabled'] = "0";  //0 未禁言 1禁言
                        $lianmai_data[$i]['status'] = 0;  //是否为主持麦 0主播位 1老板位 2主持位
                        //增加当前房间内的麦位数据
                        D("RoomWheat")->add($lianmai_data[$i]);
                    }
                    $room_info = LanguageroomService::getInstance() -> getDeatil($id);
                }
                /*$room_info['room_images'] =C('APP_URL'). $room_info['room_image'];
                unset($room_info['room_image']);        //销毁详情里面room_image替换成room_images;*/
                $room_info = dealnull($room_info);      //处理Null值数据
                $result = [
                    "room_info"=>$room_info,    //房间信息
                ];
                $this -> returnCode = 200;
                $this -> returnData=$result;
            }
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this -> returnData();
    }

    /**
     * 用户退出房间数据:以get方式
     * @param $token    token值
     * @param $room_id  房间id
     * @param $signature    签名md5(token+room_id_)
     * 返回值说明:当用户退出此接口,那么他的数据就会减1及在线用户列表减少此用户
     */
    public function roomout($token,$room_id,$signature){
        $data = [
            "token" => I('get.token'),
            "room_id" => I('get.room_id'),
            "signature" => I('get.signature'),
        ];
        $user_info['id'] = RedisCache::getInstance()->get($data['token']);    //用户id
        try{
            //校验数据
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            //验证数据
            if($signature !== Md5(strtolower($data['token'].$data['room_id']))){
                E("验签失败",2000);
            }
            //减少访问人数值(当前的访问人数大于0)
            $visitor_number = RoomtotalsService::getInstance()->getOneByIdField($data['room_id'],"visitor_number");
            if($visitor_number>0){
                RoomtotalsService::getInstance()->setDeces($data['room_id']);
            }
            /*RoomtotalsService::getInstance()->setDeces($data['room_id']);*/

            //在线用户列表减少数据(当前版本暂时不做)
            /*$isDelete = [
                "rooms_id" => $data['room_id'],
                "user_id" => $user_info['id'],
            ];
            RoomMemberService::getInstance()->setDelete($isDelete);*/
            $this -> returnCode = 200;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this -> returnData();
    }

    /**锁定房间接口：当前用户锁定房间后(所有的普通用户进不来)
     * @param $token token值
     * @param $room_id 房间id
     * @param $signature 签名md5(token+room_id_)
     */
    public function roomlock($token,$room_id,$signature){
        $data = [
            "token" => I('get.token'),
            "room_id" => I('get.room_id'),
            "signature" => I('get.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            //验证数据
            if($data['signature'] !== Md5(strtolower($data['token'].$data['room_id']))){
                E("验签失败",2000);
            }
            //对房间锁定操作'
            $room_lock = 1;
            LanguageroomService::getInstance() -> getByroomidUpdate($data['room_id'],$room_lock);
            $this -> returnCode = 200;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**解锁房间接口：当前用户可以进此房间
     * @param $token token值
     * @param $room_id 房间id
     * @param $signature 签名md5(token+room_id_)
     */
    public function roomdelock($token,$room_id,$signature){
        $data = [
            "token" => I('get.token'),
            "room_id" => I('get.room_id'),
            "signature" => I('get.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            //验证数据
            if($data['signature'] !== Md5(strtolower($data['token'].$data['room_id']))){
                E("验签失败",2000);
            }
            //对房间锁定操作'
            $room_lock = 0;
            LanguageroomService::getInstance() -> getByroomidUpdate($data['room_id'],$room_lock);
            $this -> returnCode = 200;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }
    /**
     * 用户房间修改值
     * @param $token    token值
     * @param $room_id  room_id值
     * @param $roominfo  修改的房间信息
     * @param $signature    验签md5(token+room_id）
     * http://localhost/zhibo/index.php/Api/Member/login?username=15210974319&password=123456
     */
    public function roomupdate($token,$room_id,$roominfo,$signature){
        $data = [
            "token" => I('post.token'),
            "room_id" => I('post.room_id'),
            "signature" => I('post.signature'),
        ];
//        var_dump(123);die();
        try{
            //校验数据
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            //验证数据
            if($data['signature'] !== Md5(strtolower($data['token'].$data['room_id']))){
                E("验签失败",2000);
            }
            //判断当前用户是否可以操作此修改操作
            $roominfo = stripslashes($roominfo);  //过滤数据
            $roominfo = json_decode($roominfo, true); //将josn转化为数组
            $roominfo = $this->sanitationProfile($roominfo);  //设置对应的字段
//            var_dump($roominfo);die();
            $result_keys = array_keys($roominfo);    //获取修改的键
            $result_values = array_values($roominfo);    //获取修改的值
            if($result_keys[0] == "room_name"){
                $pattern = "/^[\x{4e00}-\x{9fa5}]{1,16}+$/u";
                if( !preg_match($pattern,$result_values[0])){
                    E("请输入有效的房间名称",2000);
                }
            }else if($result_keys[0] == "room_desc"){
                $pattern = "/^[\x{4e00}-\x{9fa5}]{1,1000}+$/u";
                if( !preg_match($pattern,$result_values[0])){
                    E("请输入有效的玩法介绍",2000);
                }
            }else if($result_keys[0] == "room_welcomes"){
                $pattern = "/^[\x{4e00}-\x{9fa5}]{1,120}+$/u";
                if( !preg_match($pattern,$result_values[0])){
                    E("请输入有效的欢迎语",2000);
                }
            }

            //数据操作
            $result =  LanguageroomService::getInstance() -> getUpdate($data['room_id'],$result_keys[0],$result_values[0]);
            $this -> returnCode = 200;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }

        $this->returnData();
    }

    /**
     * 净化用户房间输入的资料.
     * @param array $profile.
     * @return array 净化之后的用户信息.
     */
    private function sanitationProfile($roominfo)
    {
        $filter_map = array(
            'room_name' => 'htmlspecialchars',  //房间名称
            'room_desc' => 'htmlspecialchars',  //玩法介绍
            'room_welcomes' => 'htmlspecialchars',      //欢迎语
            'room_type' => 'intval',            //房间分类
        );

        foreach ($filter_map as $field => $func) {
            if (!isset($roominfo[$field])) {
                continue;
            }
            $roominfo[$field] = $func($roominfo[$field]);
        }

        return $roominfo;
    }

    /**房间玩法数据接口
     * @param $token    token值
     * @param $room_id  房间id
     * @param $signature    签名md5(strtolower(token+room_id)
     */
    public function roomdesc($token,$room_id,$signature){
        $data = [
            "token" => I('post.token'),
            "room_id" => I('post.room_id'),
            "signature" => I('post.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            //验证数据
            if($data['signature'] !== Md5(strtolower($data['token'].$data['room_id']))){
                E("验签失败",2000);
            }
            $deatil = LanguageroomService::getInstance() -> getDeatil($data['room_id']);
            $deatil = dealnull($deatil);      //处理Null值数据
            $result = [
                "room_desc"=>$deatil['room_desc'],    //房间信息
            ];
            $this -> returnCode = 200;
            $this -> returnData=$result;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }

    /**房间二级页面详情接口
     * @param $token    token值
     * @param $room_id  房间id
     * @param $signature 签名md5(strtolower(token+room_id)
     */
    public function room_detail($token,$room_id,$signature){
        $data = [
            "token" => I('post.token'),
            "room_id" => I('post.room_id'),
            "signature" => I('post.signature'),
        ];
        try{
            //校验数据
            ParamCheck::checkInt("room_id",$data['room_id'],1);
            //验证数据
            /*if($data['signature'] !== Md5(strtolower($data['token'].$data['room_id']))){
                E("验签失败",2000);
            }*/
            $room_detail = [];
            $deatil = LanguageroomService::getInstance() -> getDeatil($data['room_id']);
//            var_dump($deatil);die;
            $room_detail['room_id'] = $deatil['room_id'];
            $room_detail['room_name'] = $deatil['room_name'];
            $room_detail['room_desc'] = $deatil['room_desc'];
            $room_detail['room_welcomes'] = $deatil['room_welcomes'];
            $room_detail['room_type'] = $deatil['room_type'];
            $room_detail['hx_room']= $deatil['hx_room'];
            $room_detail = dealnull($room_detail);      //处理Null值数据
            //统计当前房间内的管理员人数
            $manager_count = RoomManagerService::getInstance()->setCount($data['room_id']);
            //统计当前禁言人数
            $debar_totals = DebarService::getInstance()->setCount($data['room_id']);
            $result = [
                "room_desc" => $room_detail,    //房间信息
                "manager_count" => $manager_count,     //当前房间管理员人数
                "manager_total" => 20,                  //总共管理员人数(每个房间最多20个管理员)
                "debar_count" => $debar_totals,     //当前房间禁言人数
            ];

            $this -> returnCode = 200;
            $this -> returnData = $result;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }

    /**根据用户查出对应的创建房间列表
     * @param $token    token值
     * @param $signature    签名 MD5（strtolower(token)）
     * 说明:根据当前用户查出对应的创建房间信息:对于锁的房间有一状态,对于是否语言直播有一状态(管理员有列表,当前自己创建的房间优先)
     */
    public function myRoom($token,$signature){
        $data = [
            "token" => $_REQUEST['token'],
            "signature" =>$_REQUEST['signature'],
        ];
        $user_info['user_id'] = RedisCache::getInstance()->get($data['token']);
        try{
            //验证数据
            if($data['signature'] !== Md5(strtolower($data['token']))){
                E("验签失败",2000);
            }
            $myroom_list = LanguageroomService::getInstance() -> getMyRoom( $user_info['user_id']);
            if($myroom_list){
                foreach($myroom_list as $key=>$value){
                    $myroom_list[$key]['room_image'] =MemberService::getInstance()->getOneByIdField($value['user_id'],"avatar");//用户头像
                    if(empty($myroom_list[$key]['room_image'])){
                        $myroom_list[$key]['room_image'] = C('APP_URL').'/Public/Uploads/image/logo.png';
                    }else{
                        $myroom_list[$key]['room_image'] = C('APP_URL').MemberService::getInstance()->getOneByIdField($value['user_id'],"avatar");//用户头像
                    }
                    $myroom_list[$key]['nickname'] = MemberService::getInstance()->getOneByIdField($value['user_id'],"nickname");  //用户昵称
                    $myroom_list[$key]['room_type'] = D('RoomMode')->getOneByIdField($value['room_type'],"room_mode");         //房间类型
                    unset($myroom_list[$key]['user_id']);
                    $myroom_list = dealnull($myroom_list);      //处理Null值数据
                }
            }else{
                $myroom_list = [];
            }
            //查找我管理房间的所有列表功能接口RoomManagerService
//            $room_memberlist = RoomMemberService::getInstance() -> getMyRoom( $user_info['user_id']);
            $room_memberlist = RoomManagerService::getInstance() -> getMyRoom( $user_info['user_id']);
            if($room_memberlist){
                foreach($room_memberlist as $key=>$value){
                    //根据房间获取创建人的用户头像
                    $values['user_id'] = LanguageroomService::getInstance() -> getOneByIdField($value['room_id'],"user_id");
                    $room_memberlist[$key]['room_image'] =MemberService::getInstance()->getOneByIdField($values['user_id'],"avatar");//创建房间人的用户头像
                    if(empty($room_memberlist[$key]['room_image'])){
                        $room_memberlist[$key]['room_image'] = C('APP_URL').'/Public/Uploads/image/logo.png';
                    }else{
                        $room_memberlist[$key]['room_image'] = C('APP_URL').MemberService::getInstance()->getOneByIdField($values['user_id'],"avatar");//用户头像
                    }
                    $room_memberlist[$key]['nickname'] = MemberService::getInstance()->getOneByIdField($values['user_id'],"nickname");  //用户昵称
                    $room_memberlist[$key]['room_type'] = D('RoomMode')->getOneByIdField($value['room_type'],"room_mode");         //房间类型
                    unset($room_memberlist[$key]['user_id']);
                    $room_memberlist = dealnull($room_memberlist);      //处理Null值数据
                }
            }else{
                $room_memberlist = [];
            }
            $result = [
                "myroom_list"=> $myroom_list,    //我创建的房间信息
                "myroom_member" => $room_memberlist,      //我的管理员列表
            ];
            $this -> returnCode = 200;
            $this -> returnData=$result;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }

    /**首页轮播图列表接口
     * @param $token
     */
    public function bannerlist($token){
        $data = [
            "token" => I('post.token'),
            "type" => I("post.type"),
        ];
        //轮播类型1首页 2房间
        if($data['type'] == 2){
            $data['type'] = 2;
        }else{
            $data['type'] = 1;
        }
        try{
            //验证数据
            /*if($data['signature'] !== Md5(strtolower($data['token']))){
                E("验签失败",2000);
            }*/
            //获取轮播图数据
//            $type = 1;  //轮播类型1首页 2房间
            if($data['type'] == 1){
                $banner_listes = RedisCache::getInstance()->get("list_bannerindex");     //获取缓存数据
                if(empty($banner_listes)){
                    $banner = D('banner')->getAppUrl($data['type']);
                    if($banner){
                        foreach ($banner as $key => $value) {
//                        $banner[$key]['linkurl'] = $value['linkurl'].'?mtoken='.$data['token'];
                            $banner[$key]['linkurl'] = $value['linkurl'];
                        }
                    }else{
                        $banner = [];
                    }
                    //存入缓存操作
                    $expired_time = 7 * 24 * 60 * 60; // 一周时间
                    RedisCache::getInstance()->set("list_bannerindex",json_encode($banner));
                    RedisCache::getInstance()->expireAt("list_bannerindex",$expired_time);     //设置缓存时间
                }else{      //读取缓存数据
                    $banner = json_decode($banner_listes,true);
                }
            }else{
                $banner_listes = RedisCache::getInstance()->get("list_bannerroom");     //获取缓存数据
                if(empty($banner_listes)){
                    $banner = D('banner')->getAppUrl($data['type']);
                    if($banner){
                        foreach ($banner as $key => $value) {
//                        $banner[$key]['linkurl'] = $value['linkurl'].'?mtoken='.$data['token'];
                            $banner[$key]['linkurl'] = $value['linkurl'];
                        }
                    }else{
                        $banner = [];
                    }
                    //存入缓存操作
                    $expired_time = 7 * 24 * 60 * 60; // 一周时间
                    RedisCache::getInstance()->set("list_bannerroom",json_encode($banner));
                    RedisCache::getInstance()->expireAt("list_bannerroom",$expired_time);     //设置缓存时间
                }else{      //读取缓存数据
                    $banner = json_decode($banner_listes,true);
                }
            }
            
            foreach ($banner as $key => $value) {
                $banner[$key]['linkurl'] = $value['linkurl'].'?mtoken='.$data['token'];
            }
            $result = [
                "bannerList"=> $banner,    //首页轮播接口
            ];
            $this -> returnCode = 200;
            $this -> returnData=$result;
            $this -> returnData=$result;
        }catch (Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this -> returnData();
    }




}


?>