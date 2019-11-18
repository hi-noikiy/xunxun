<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\Easemob;
use Think\Exception;
use Think\Log;

class AttentionController extends BaseController
{
    
    //用户关注接口
    public function attention_member($token,$userided,$type){
        try{
            $userid = RedisCache::getInstance()->get($token);
            if($type==1){        
                $data=array('userid'=>$userid,'userided'=>$userided,"attention_time"=>time());
                D('attention')->addData($data);           
            }elseif($type==2){
                $where=array('userid'=>$userid,'userided'=>$userided);
                D('attention')->del($where);
            }
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
            $this -> returnData();
    }
    
    //关注，粉丝列表接口
    public function attention_list($token,$type,$page){
        $token=$_REQUEST['token'];
        $type=$_REQUEST['type'];
        $page=$_REQUEST['page'];
        try{
          $user_info=array();
          $field='id as userid,username,sex,nickname,intro,avatar,birthday,roomnumber,is_vip';       
          $userid = RedisCache::getInstance()->get($token);
      //    var_dump($userid);die;
          if($type==1){
          $where=array('userid'=>$userid);
          $fields="userided";
          }elseif($type==2){
           $where=array('userided'=>$userid);
           $fields="userid";
          }
          // 每页条数
          $size = 10;
          $pageNum = empty($size)?10:$size;
          $page = empty($page)?1:$page;
          $count =D('attention')->attentioncount($where);
         // var_dump($count);die;
          // 总页数.
          $totalPage = ceil($count/$pageNum);
          // 页数信息.
          $pageInfo = array("page" => $page, "pageNum"=>$pageNum, "totalPage" => $totalPage);
          $limit = ($page-1) * $size . "," . $size;
          $list=D('attention')->getlist($where,$fields,$limit);        
          foreach($list as $ke=>$v){
              //var_dump($v['userided']);die;
              if($type==1){
                $userids=$v['userided'];
               $status=1;//已经关注
              }elseif($type==2){
                $userids=$v['userid'];
               $wheresss=array('userid'=>$userid,'userided'=>$userids);
                $lists=D('attention')->getlist($wheresss);
            //   var_dump($lists);die;
                if($lists){
                    $status=1;//已经关注
                }else{
                    $status=2;//未关注
                }
              }
              $user_infos = D('member')->getOneByIdField($userids,$field);
                if($user_infos){
                  $user_infos[$userids]['status']=$status;
                  if( $user_infos[$userids]['avatar']==""){
                      
                  }else{
                      $user_infos[$userids]['avatar']=C('APP_URL'). $user_infos[$userids]['avatar'];
                  }  
              }else{
                  $user_infos=[];
              }
            $user_info[]=dealnull($user_infos[$userids]);  
          }
          $result=[
              'user_info'=>$user_info,
              'pageinfo'=>$pageInfo,
          ];
          $this -> returnCode = 200;
          $this -> returnMsg = "操作成功";
          $this -> returnData=$result;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
        $this -> returnData();
    }
    
        //移除粉丝接口
    public function del_fans($token,$userided){
        try{
            $userid = RedisCache::getInstance()->get($token);
            $data=array('userid'=>$userided,'userided'=>$userid);
            $del_fans=D('attention')->del($data);
            //var_dump($del_fans);die;
            if($del_fans){
                $this -> returnCode = 200;
                $this -> returnMsg = "操作成功";
            }
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
            
        }
        $this -> returnData();
    }

}
