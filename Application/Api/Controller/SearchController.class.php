<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Api\Service\RoomManagerService;
use Api\Service\LanguageroomService;
use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Think\Log;
use Common\Util\RedisCache;

class SearchController extends BaseController {
    /**管理中搜索功能(根据用户昵称,用户id)
     * 搜索用户接口.重点监控此带like的接口。根据搜索的压力情况，考虑是否使用elasticsearch(这里应用的是精确搜索功能)
     * @param $token token值
     * @param $search   搜索的值
     * @param $room_id  房间id值
     * @param $signature    签名(md5(token))
     * type 1 模糊搜索  2 精准搜索人数3 精准搜索房间
     */
    public function searchmsg($token,$search,$type){
        try{
            $userid = RedisCache::getInstance()->get($token);
            if($type==1){//模糊搜索 房间，用户(id,昵称)   
                //判断有缓存，读取缓存
                $roomKey = 'search_room_list_'.md5($search);
                $memberKey = 'search_member_list_'.md5($search);
                $redisRoomData = RedisCache::getInstance()->get($roomKey);
                $redisMemberData = RedisCache::getInstance()->get($memberKey);
                $memberres = RedisCache::getInstance()->get('member_list'.$userid);
                $roomres = RedisCache::getInstance()->get("room_list".$userid);
                if (($redisRoomData || $redisMemberData) && ($memberres || $roomres)) {
                    $result = [
                        "room_list" => json_decode($redisRoomData),
                        "member_list" => json_decode($redisMemberData),
                    ]; 
                    $this -> returnCode = 200;
                    $this -> returnData=$result;
                    $this->returnData();
                }

                $where['id'] = array("LIKE", '%' . $search . '%');
                $where['pretty_room_id'] = array("LIKE", '%' . $search . '%');
                $where['room_name'] = array("LIKE", '%' . $search . '%');
                $where['_logic'] = 'or';
                //判断版本不显示电台
                $banben = explode(',', $this->clientVersion);
                if (version_compare($banben[0], '2.2.0', '<')) {
                    $map['_complex'] = $where;          
                    $map['room_type'] = array("NEQ", '6');

                }else{
                    $map = $where;
                }
                $room_lists =D('languageroom')->Search($search,$map,'id desc');
                // $searchKey = 
                $redisroom = RedisCache::getInstance()->get('room_list'.$userid);
                //var_dump($token);die;
                if ($redisroom) {
                    RedisCache::getInstance()->delete('room_list'.$userid);
                }
          
                $room_list=array();
                foreach($room_lists as $key=>$value){
                    if($key<3){
                        $room_mode=D('room_mode')->getOneByIdField($room_lists[$key]['room_type'],'room_mode');
                        $room_lists[$key]['room_type']=$room_mode;
                        $room_lists[$key]['room_image'] = C('APP_URL').$value['room_image'];   //房间图
                        $room_list[]=dealnull($room_lists[$key]);
                        RedisCache::getInstance()->getRedis()->SETEX($roomKey,60,json_encode($room_list));
                    }

                }
                 RedisCache::getInstance()->getRedis()->SETEX('room_list'.$userid,60,json_encode($room_lists));
                 
                $wheres['pretty_id'] = array("LIKE", '%' . $search . '%');
                $wheres['nickname'] = array("LIKE", '%' . $search . '%');
                $wheres['_logic'] = 'or';
                // $member_lists= MemberService::getInstance()->Search($search,$wheres,'pretty_id desc');
                $member_lists= D('member')->NewSearch($search);
                // $redismember = RedisCache::getInstance()->get('member_list'.$userid);
                // //var_dump($token);die;
                // if ($redismember) {
                //     RedisCache::getInstance()->delete('member_list'.$userid);
                // }
          
                $member_list=array();
                foreach($member_lists as $key=>$value){
                    if($key<3){
                        $member_lists[$key]['avatar'] = C('APP_URL').$value['avatar'];   //用户头像
                        $member_list[]=dealnull($member_lists[$key]);
                        RedisCache::getInstance()->getRedis()->SETEX($memberKey,60,json_encode($member_list));
                    }

                }
                $result = [
                    "room_list" => empty($room_list)?[]:$room_list,
                    "member_list" => empty($member_list)?[]:$member_list,
                ]; 
                RedisCache::getInstance()->getRedis()->SETEX('member_list'.$userid,60,json_encode($member_lists));
                // if($room_list ||$member_list){
                //     $result = [
                //         "room_list" => empty($room_list)?[]:$room_list,
                //         "member_list" => empty($member_list)?[]:$member_list,
                //     ];                  
                // }else{
                //     $result = [
                //         "room_list" => [],
                //         "member_list" => [],
                //     ];      
                // }
            }elseif($type==2){//精准搜索人(按照 id 昵称)
                $where['pretty_id'] = $search;
                $where['nickname'] = $search;
                $where['_logic'] = 'or'; 
                $member_lists= MemberService::getInstance()->Search($search,$where);
                //var_dump($member_list['user_id']);die;
                $member_list=array();
                foreach($member_lists as $key=>$value){
                    $member_lists[$key]['avatar'] = C('APP_URL').$value['avatar'];   //用户头像
                    //$member_list[$key]['is_manager'] = RoomManagerService::getInstance()->isRoomManager($data['room_id'],$value['user_id']);   //当前用户是否为房间的管理员 0非管理员 1管理员\
                   // if($member_list[$key]['is_manager']){
                     //   $member_list[$key]['is_manager'] = 1;
                  //  }else{
                       // $member_list[$key]['is_manager'] = 0;
                   // }
                     $member_list[]=dealnull($member_lists[$key]);
                }
              
                if($member_list){
                 $result = [
                        "member_list" => $member_list,
                    ];         
                }else{
                                  $result = [
                        "member_list" => [],
                    ];       
                }
            }elseif($type==3){//精准搜索房间 (id,昵称)
                $where['id'] = $search;
                $where['room_name'] = $search;
                $where['_logic'] = 'or';
                $room_lists =D('languageroom')->Search($search,$where);
                $room_list=array();
                foreach($room_lists as $key=>$value){
                    $room_mode=D('room_mode')->getOneByIdField($room_lists[$key]['room_type'],'room_mode');
                    $room_lists[$key]['room_type']=$room_mode;
                    $room_lists[$key]['room_image'] = C('APP_URL').$value['room_image'];   //用户头像
//                     $room_list[$key]['is_manager'] = RoomManagerService::getInstance()->isRoomManager($data['room_id'],$value['user_id']);   //当前用户是否为房间的管理员 0非管理员 1管理员\
//                     if($room_list[$key]['is_manager']){
//                         $room_list[$key]['is_manager'] = 1;
//                     }else{
//                         $room_list[$key]['is_manager'] = 0;
//                     }
                        $room_list[]=dealnull($room_lists[$key]);
                }
                if($room_list){
                  $result = [
                        "room_list" => $room_list,
                    ];           
                }else{
                        $result = [
                        "room_list" => [],
                    ];     
                }          
              }else{
                    E('参数不正确',2001);
              }

            $this -> returnCode = 200;
            $this -> returnData=$result;
        }catch (\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
    }
    //查看更多接口
    public function getmoremsg($token,$is_more){
        try{
            $userid = RedisCache::getInstance()->get($token);
            $member_lists = RedisCache::getInstance()->get('member_list'.$userid);
            $member_lists=json_decode($member_lists);
            $member_lists=object_array($member_lists);
            $room_lists = RedisCache::getInstance()->get("room_list".$userid);
            $room_lists=json_decode($room_lists);
            $room_lists=object_array($room_lists);
            $member_list=array();
            $room_list=array();
            if($is_more=="1"){
                foreach($member_lists as $key=>$value){
//                     $member_lists[$key]['avatar'] = C('APP_URL').$value['avatar'];   //用户头像
                    $member_lists[$key]['avatar'] =getavatar($value['avatar']);   //用户头像
                    $member_list[]=dealnull($member_lists[$key]);
                }
                $result = [
                    "member_list" => $member_list,
                ];
            }elseif($is_more=="2"){
                foreach($room_lists as $key=>$value){

//                    $room_lists[$key]['room_image'] = C('APP_URL').$value['room_image'];   //用户头像
                    $room_lists[$key]['room_image'] = getavatar($value['room_image']);   //用户头像
                    $room_list[]=dealnull($room_lists[$key]);
                }
                $result = [
                    "room_list" => $room_list,
                ];
            }
            $this -> returnCode = 200;
            $this -> returnData=$result;
        }catch (\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

    }


    /**最近访问房间列表
     * @param $token    token值
     */
    public function visited($token){
        //获取token值
        $data['token'] = $_REQUEST['token'];
        try{
//            $userid = RedisCache::getInstance()->get($token);
            $user_id = RedisCache::getInstance()->get($data['token']);
            $redisKey = $user_id;
            $redis = new \Redis();
            $redis->connect(C('REDIS_HOST'),C('REDIS_PORT'));
            $redis->auth(C('REDIS_PWD'));
            $redis->select(2);
            $rank = $redis->ZRANGE($redisKey, 0, -1, 'WITHSCORES');
//            var_dump($rank);die();
            if($rank){
                $ranklist = array();
                foreach ($rank as $key => $value) {
                    $ranklist[$key] = $value;
                }
//                $rankUser = array_slice($ranklist, 0, 10, true);
                $rankUserId = array_reverse(array_keys($ranklist));
                //取出前十个用户
                $rankUserId = array_slice($rankUserId,0,10);
                $uidStr = implode(',', $rankUserId);
                $where['id'] = array('in',$uidStr);
//                var_dump($rankUserId);die();
                $room_result = M('languageroom')->field('id as room_id,user_id,room_name,room_image,room_type,pretty_room_id')->where($where)->select();
//                echo M("languageroom")->getLastSql();die();
                if($room_result){
                    foreach($room_result as $key=>$value){
                        $room_result[$key]['room_image'] = getavatar(M("member")->where(array("id"=>$value['user_id']))->getField("avatar"));
                        $room_result[$key]['room_type'] = M("room_mode")->where(array("id"=>$value['room_type']))->getField("room_mode");
                    }
                    //根据每条记录的字段id，去$b中查找对应的键值，作为这一条记录的键值。
                    $newarr = [];
                    foreach ($room_result as $v) {
                        $k = array_search($v['room_id'],$rankUserId);
                        $newarr[$k] = $v;
                    }
                    ksort($newarr);
                    $newarr = array_values($newarr);
                }else{
                    $newarr = [];
                }

            }else{
                $newarr = [];
            }
            $room_result = [
                "room_msg" => $newarr,
            ];

            $this -> returnCode = 200;
            $this -> returnData = $room_result;
        }catch (\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
    }

}


?>