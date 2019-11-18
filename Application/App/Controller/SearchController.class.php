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
                $where['id'] = array("LIKE", '%' . $search . '%');
                $where['room_name'] = array("LIKE", '%' . $search . '%');
                $where['_logic'] = 'or';
                $room_lists =D('languageroom')->Search($search,$where,'id desc');
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
                        $room_lists[$key]['room_image'] = C('APP_URL').$value['room_image'];   //用户头像
                        $room_list[]=dealnull($room_lists[$key]);
                    }

                }
                 RedisCache::getInstance()->set('room_list'.$userid,json_encode($room_lists));
                $wheres['id'] = array("LIKE", '%' . $search . '%');
                $wheres['nickname'] = array("LIKE", '%' . $search . '%');
                $wheres['_logic'] = 'or';
                $member_lists= MemberService::getInstance()->Search($search,$wheres,'id desc');
                $redismember = RedisCache::getInstance()->get('member_list'.$userid);
                //var_dump($token);die;
                if ($redismember) {
                    RedisCache::getInstance()->delete('member_list'.$userid);
                }
          
                $member_list=array();
                foreach($member_lists as $key=>$value){
                    if($key<3){
                        $member_lists[$key]['avatar'] = C('APP_URL').$value['avatar'];   //用户头像
                        $member_list[]=dealnull($member_lists[$key]);
                    }

                }
                RedisCache::getInstance()->set('member_list'.$userid,json_encode($member_lists));
                if($room_list ||$member_list){
                    $result = [
                        "room_list" => $room_list,
                        "member_list" => $member_list,
                    ];                  
                }else{
                    $result = [
                        "room_list" => [],
                        "member_list" => [],
                    ];      
                }
            }elseif($type==2){//精准搜索人(按照 id 昵称)
                $where['id'] = $search;
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
                     $member_lists[$key]['avatar'] = C('APP_URL').$value['avatar'];   //用户头像
                    $member_list[]=dealnull($member_lists[$key]);
                }
                $result = [
                    "member_list" => $member_list,
                ];
            }elseif($is_more=="2"){
                foreach($room_lists as $key=>$value){

                    $room_lists[$key]['room_image'] = C('APP_URL').$value['room_image'];   //用户头像
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
    
        //最近访问房间
    public function visited($token){
        try{
            $userid = RedisCache::getInstance()->get($token);
           // var_dump($userid);die;
            $room_id=D('room_member')->findRooms($userid,'rooms_id',"creattime desc",10);
          //  var_dump($room_id);die;
            $room_msg=array();
            if($room_id){
                foreach($room_id as $k=>$v){
                   $room= D("languageroom")->getDeatil($v['rooms_id']);
                   $room_mode=D('room_mode')->getOneByIdField($room['room_type'],'room_mode');
                   $room['visitor_number']=$room_total;
                   $room['room_type']=$room_mode;
                   $room['room_image']=C("APP_URL").$room['room_image'];
                   $room=dealnull($room);
                   $room_msg[]=$room;
                }
                $result = [
                    "room_msg"=>$room_msg,
                ];
            }else{
                          $result = [
                    "room_msg"=>[],
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

}


?>