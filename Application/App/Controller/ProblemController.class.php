<?php

namespace Api\Controller;

use Think\Controller;

class ProblemController extends BaseController {
    /**
     * @param $token token值
     * @param $type 1表示房间 2表示用户
     * @param $signature 校验md5(token）
     */
	public function getList($token,$type,$signature){
        $data = [
            "token"=>I("post.token"),
            "type"=>I("post.type"),
            "signature"=>I('post.signature'),
        ];
		try{
            //校验签名
            /*if($data['signature'] !== md5(strtolower($data['token']))){
                E("验签失败",2000);
            }*/
            if($data['type'] == 1){
                $where['btypeid'] =  1;
                $data = D("problem")->getList($where);
            }else{
                $typearr = ['1','2'];
                $where['btypeid'] =  ['in', $typearr];
                $data = D("problem")->getList($where);
            }
            $problem = [
                "problem_list"=>$data,
            ];

			// var_dump($problem);die();
			//查询成功
			$this -> returnCode = 200;
			$this -> returnData=$problem;
		}catch(\Exception $e){
			$this -> returnCode = $e ->getCode();
			$this -> returnMsg = $e ->getMessage();
		}
		$this->returnData();

	}

    /**
     * 举报接口
     * @param $token
     * @param $room_id
     * @param null $signature
     */
    public function report_room($token,$report_roomid,$report_content){
        $data = [
            "token"=>I("get.token"),
            "signature"=>I('get.signature'),
        ];
        try{
            //校验签名
            /*if($data['signature'] !== md5(strtolower($data['token']))){
             E("验签失败",2000);
             }*/
            $where=array('userid'=>$token,"report_roomid"=>$report_roomid,'status'=>"0");
            $status=D('report')->getbymsg($where);
            //var_dump($status);die;
            if($status){
                E('您已经举报过该房间',2000);
            }else{
                $report_msg=array(
                    'userid'=>$token,
                    "report_roomid"=>$report_roomid,
                    "report_content"=>$report_content,
                    'report_time'=>time(),
                    
                );
                $data = D("report")->addData($report_msg);
                //var_dump($data);die;
                if($data){
                    $this -> returnCode = 200;
                }else{
                    E("举报失败",2000);
                }
            }
            $this->returnData();
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

    }
    
    /**
     * @param $token token值
     * @param $signature 校验md5(token）
               * 踢人接口 
     */
    public function KickMember($token,$userid,$room_id,$maixu=null){
        try{
            //校验签名
            /*if($data['signature'] !== md5(strtolower($data['token']))){
             E("验签失败",2000);
             }*/
            //点击移除房间像数据库记录一条数据移除三次24小时之内不能进入房间   
            //var_dump($room_id);die;
            //踢处麦上的人
            $file="updown/".$roomid.'.txt';
            if($maixu!=null){
                if(file_exists($file)){
                    if(false!==fopen($file,'w+')){
                        $handle=fopen($file,'r');
                        $cacheArray=unserialize(fread($handle,filesize($file)));
                        $cacheArray[$maixu][$maixu]=null;
                        file_put_contents($file,serialize($cacheArray));
                    }

            }
           }
           $where=array('userid'=>$userid,'room_id'=>$room_id);
            $field="kick_number";
            $kick_msg=D('kickmember')->getbymsg($where,$field);
            //var_dump($kick_msg);die;
            if($kick_msg!=null){
                $data=array(
                    'kick_number'=>$kick_msg+1,
                    'kick_time'=>time(),
                );
                //var_dump($data);die;
                $data = D("kickmember")->updateData($where,$data);
            }else{
                $data=array(
                    'userid'=>$userid,
                    'room_id'=>$room_id,
                    'kick_number'=>1,
                    'kick_time'=>time(),
                );
                $data = D("kickmember")->addData($data);
            }

            $this -> returnCode = 200;
            $this -> returnData=$problem;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
        
    }

}


?>