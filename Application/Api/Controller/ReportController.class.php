<?php
namespace Api\Controller;

use Think\Controller;
use Think\Exception;
use Think\Log;
use Common\Util\RedisCache;

class ReportController extends BaseController {

    //举报选项
	public function option()
	{
		$result = D('forum_report_option')->getlistall('id as report_option_id,report_option_content');
		$this -> returnCode = 200;
        $this -> returnData = $result;
        $this -> returnMsg = '查询成功';
        $this->returnData();
	}

    //添加举报
	public function report()
	{
		$token = I('post.token');
        $forum_id = I('post.forum_id');
        $report_content = I('post.report_content');
        $report_option_id = I('post.report_option_id');
        $reply_id = I('post.repely_id');

        if (!$token || !$forum_id || !$report_content || !$report_option_id) {
        	$this -> returnCode = 500;
            $this -> returnMsg = '参数错误';
            $this->returnData();
        }
        try{
        	$userid = RedisCache::getInstance()->get($token);
            if (!$userid) {
            	E("用户登录信息错误", 500);
            }
            $time = time();
            if (!empty($reply_id)) {
                $getReport = array('forum_id'=>$forum_id,'report_uid'=>$userid,'reply_id'=>$reply_id);
                $addParam = array('forum_id'=>$forum_id,'report_uid'=>$userid,'reply_id'=>$reply_id,'report_option_id'=>$report_option_id,'report_content'=>$report_content,'createtime'=>$time,'updatetime'=>$time);
            }else{
                $getReport = array('forum_id'=>$forum_id,'report_uid'=>$userid);
                $addParam = array('forum_id'=>$forum_id,'report_uid'=>$userid,'report_option_id'=>$report_option_id,'report_content'=>$report_content,'createtime'=>$time,'updatetime'=>$time);
            }
            $res = D('forum_report')->getOne($getReport,'id,forum_id');
            if (empty($res)) {
        		
            	$data = D('forum_report')->addData($addParam);
            	if ($data) {
            		$this -> returnCode = 200;
            		$this -> returnMsg = '举报成功';
            	}else{
            		$this -> returnCode = 500;
            		$this -> returnMsg = '举报失败';
            	}
            }else{
            	E("已经举报过", 500);
            }
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();


	}


    /**
     * 点赞
     * @param $token token值
     * @param $enjoyid  点赞贴id
     * @param $type 1点赞2取消赞
     */
    public function enjoy(){
        //获取数据
        $token = I('post.token');
        $forum_id = I('post.forum_id');
        $type = I('post.type');
        if (!$token || !$forum_id || !$type) {
        	$this -> returnCode = 500;
            $this -> returnMsg = '参数错误';
            $this->returnData();
        }
        try{
        	$userid = RedisCache::getInstance()->get($token);
            if (!$userid) {
            	E("用户登录信息错误", 500);
            }
            $time = time();
            $res = D('forum_enjoy')->getOne(array('forum_id'=>$forum_id,'enjoy_uid'=>$userid),'enjoy_uid,forum_id,createtime');
            if ($type == 1) {
            	if (empty($res)) {
            		$addParam = array('forum_id'=>$forum_id,'enjoy_uid'=>$userid,'createtime'=>$time,'updatetime'=>$time);
	            	D('forum_enjoy')->addData($addParam);
	            	$result = 2;
	            }else{
	            	E("已经点赞过", 500);
	            }
            }else{
            	if (empty($res)) {
            		E("您还没有点赞过", 500);
	            }else{
	            	$where = array('forum_id'=>$forum_id,'enjoy_uid'=>$userid);
	            	D('forum_enjoy')->del($where);
	            	$result = 1;
	            }
            }
            $this -> returnCode = 200;
            $this -> returnData=$result;
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

    }

}


?>