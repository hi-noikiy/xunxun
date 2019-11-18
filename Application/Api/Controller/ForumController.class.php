<?php
namespace Api\Controller;

use Think\Controller;
use Think\Exception;
use Think\Log;
use Common\Util\RedisCache;
use Common\Util\emchat\Easemob;

class ForumController extends BaseController {
    private $tagKey = 'forum_tags_num';
    private $topicKey = 'forum_topic_num_';

    //评论列表
    public function replylist()
    {
        $token = I('post.token');
        $forum_id = I('post.forum_id');
        $page = I('post.page');
        $pagenum = I('post.pagenum');
        if (!$token || !$forum_id || !$page || !$pagenum) {
            $this -> returnCode = 500;
            $this -> returnMsg = '参数错误';
            $this->returnData();
        }
        try{
            $userid = RedisCache::getInstance()->get($token);
            if (!$userid) {
                E("用户登录信息错误", 500);
            }

            $forumRes = D('forum')->getOne(array('id'=>$forum_id),'id,forum_uid');
            if (!$forumRes) {
                E("当前动态不存在",500);
            }else{
                $field = 'id as reply_id,reply_content,reply_uid,reply_atuid,createtime,reply_type';
                $start = ($page-1)*$pagenum;
                $limit = $start.','.$pagenum;
                
                $data['list'] = [];
                $data['page'] = $page;
                $data['pagenum'] = $pagenum;
                $data['pagecount'] = D('forum_reply')->getAllcountNum($forum_id);
                $data['list'] = $this->getReplyList($forum_id,$field,$limit,$forumRes['forum_uid']);
                $this -> returnCode = 200;
                $this -> returnData = $data;
                $this->returnData();
            }
            
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
    }

    public function getReplyList($forum_id,$field,$limit,$forum_uid)
    {
        $result = D('forum_reply')->getlist(array('forum_id'=>$forum_id,'reply_status'=>1),$field,$limit);
        if ($result) {
            $ruidStr = '';
            foreach ($result as $key => $value) {
                $ruidStr .= $value['reply_uid']. ','.$value['reply_atuid']. ',';
            }
            $ruidStr = trim($ruidStr,',');
            // $uidRes = D('member')->getlist(array('id'=>array('in',$ruidStr)),'id as uid,avatar,nickname,sex');
            $uidRes = D('member')->getUserByInid(array('id'=>array('in',$ruidStr)),'id as uid,avatar,nickname,sex');
            foreach ($result as $key => $value) {
                $result[$key]['createtime'] = date('Y-m-d H:i:s',$value['createtime']);
                $result[$key]['reply_uid_avatar'] = $uidRes[$value['reply_uid']]['avatar']?C('APP_URL_image').'/'.$uidRes[$value['reply_uid']]['avatar']:'';
                if ($value['reply_uid'] == $forum_uid) {//判断楼主
                    $result[$key]['reply_uid_nickname'] = '楼主';
                }else{
                    $result[$key]['reply_uid_nickname'] = $uidRes[$value['reply_uid']]['nickname']?$uidRes[$value['reply_uid']]['nickname']:'';
                }
                
                $result[$key]['reply_atuid_avatar'] = $uidRes[$value['reply_atuid']]['avatar']?C('APP_URL_image').'/'.$uidRes[$value['reply_atuid']]['avatar']:'';
                if ($value['reply_atuid'] == $forum_uid) {
                    $result[$key]['reply_atuid_atnickname'] = '楼主';
                } else {
                    $result[$key]['reply_atuid_atnickname'] = $uidRes[$value['reply_atuid']]['nickname']?$uidRes[$value['reply_atuid']]['nickname']:'';
                }
                $result[$key]['reply_uid_sex'] = $uidRes[$value['reply_uid']]['sex']?$uidRes[$value['reply_uid']]['sex']:'';
                $result[$key]['reply_atuid_sex'] = $uidRes[$value['reply_atuid']]['sex']?$uidRes[$value['reply_atuid']]['sex']:'';
            }
        }else{
            $result = [];
        }
        return $result;
    }

    //帖子评论
    public function addreply()
    {
        if (C('FORUM')) {
            //判断安卓弹窗
            if ($this->clientPlatform !== "iOS") {
                $this -> returnCode = 2000;
            }else{
                $this -> returnCode = 500;
            }
            $this -> returnMsg = '系统升级中';
            $this->returnData();
        }
        $token = I('post.token');
        $forum_id = I('post.forum_id');
        $type = I('post.type');
        $content = $_POST['content'];
        $content = trim($content);
        $reply_atuid = I('post.reply_atuid');
        $reply_parent_id = I('post.reply_id');
        if (!$token || !$forum_id || !$type || $content == '') {
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

            $data = D('forum')->getOne(array('id'=>$forum_id),'id as forum_id,forum_uid,createtime,forum_content,forum_image,forum_voice');
            if (!$data) {
                E("当前评论动态不存在",500);
            }
            $param = array('reply_content'=>$content,"forum_id"=>$forum_id,"reply_uid"=>$userid,"reply_atuid"=>$reply_atuid,'reply_type'=>$type,'createtime'=>$time,'updatetime'=>$time,'reply_parent_id'=>$reply_parent_id);
            if ($type == 1) {
                $atid = $data['forum_uid'];
                $param['reply_atuid'] = $data['forum_uid'];
                $replyRes = D('forum_reply')->addData($param);
            }elseif($type == 2){
                if (empty($reply_atuid) || empty($reply_parent_id)) {
                    E("回复人不能为空",500);
                }
                $atid = $reply_atuid;
                $replyRes = D('forum_reply')->addData($param);
            }else{
                E("回复类型错误",500);
            }
            if ($replyRes) {
                $msgData['msg'] = $this->getPushMsg($replyRes,$param,$data);
                $msgData['content'] = $param['reply_content'];
                $msgData['reply_id'] = $replyRes;
                $msgData['forum_id'] = $data['forum_id'];
                $msgData['atuid'] = $atid;
                $redisList = json_encode($msgData);
                RedisCache::getInstance()->getRedis()->LPUSH('forum_reply_msg',$redisList);
                // RedisCache::getInstance()->getRedis()->LPUSH('forum_reply_msg',$replyRes);
                $this -> returnCode = 200;
                $this -> returnMsg = '评论成功';
            }else{
                $this -> returnCode = 500;
                $this -> returnMsg = '评论失败';
            }
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

    }

    public function getPushMsg($reply_id,$reply_data,$forum_data)
    {
        $uids = $reply_data['reply_atuid'].','.$reply_data['reply_uid'].','.$forum_data['forum_uid'];
        $userRes = D('member')->getlistAllByWhere(array('id'=>array('in',$uids)),'id as uid,nickname,avatar,sex');
        if ($reply_data['reply_type'] == 1) {//回帖
            $data['forum']['forum_id'] = $forum_data['forum_id'];
            $data['forum']['forum_uid'] = $forum_data['forum_uid'];
            $data['forum']['avatar'] = $userRes[$forum_data['forum_uid']]['avatar']?C('APP_URL_image').'/'.$userRes[$forum_data['forum_uid']]['avatar']:'';
            $data['forum']['nickname'] = $userRes[$forum_data['forum_uid']]['nickname']?$userRes[$forum_data['forum_uid']]['nickname']:'';
            $data['forum']['forum_content'] = $forum_data['forum_content']?$forum_data['forum_content']:'';
            $arr = explode(',', $forum_data['forum_image']);
            foreach ($arr as $key => &$value) {
                $value = $value?C('APP_URL_image').'/'.$value:'';
            }
            $data['forum']['forum_image'] = implode(',', $arr);
            $data['forum']['forum_voice'] = $forum_data['forum_voice']?C('APP_URL_image').'/'.$forum_data['forum_voice']:'';
            $data['forum']['sex'] = $userRes[$forum_data['forum_uid']]['sex'];
            $data['forum']['createtime'] = date('Y-m-d H:i:s',$forum_data['createtime']);
            $data['forum']['reply_num'] = D('forum_reply')->countNum(array('forum_id'=>$forum_data['forum_id'],'reply_status'=>1));
        }else{
            $repRes = D('forum_reply')->getOne(array('id'=>$reply_data['reply_parent_id']),'id as reply_id,reply_content,createtime');
            $data['reply']['reply_id'] = $repRes['reply_id'];
            $data['reply']['reply_content'] = $repRes['reply_content'];
            $data['reply']['createtime'] = date('Y-m-d H:i:s',$repRes['createtime']);
            $data['reply']['reply_num'] = D('forum_reply')->countNum(array('reply_parent_id'=>$repRes['reply_id'],'reply_status'=>1));
        }
        $data['addReply']['reply_id'] = $reply_id;
        $data['addReply']['reply_uid'] = $reply_data['reply_uid'];
        $data['addReply']['reply_uid_avatar'] = $userRes[$reply_data['reply_uid']]['avatar']?C('APP_URL_image').'/'.$userRes[$reply_data['reply_uid']]['avatar']:'';
        $data['addReply']['reply_uid_nickname'] = $userRes[$reply_data['reply_uid']]['nickname'];
        $data['addReply']['reply_uid_sex'] = $userRes[$reply_data['reply_uid']]['sex'];
        $data['addReply']['reply_content'] = $reply_data['reply_content'];

        $data['addReply']['reply_atuid'] = $reply_data['reply_atuid'];
        $data['addReply']['reply_atuid_avatar'] = $userRes[$reply_data['reply_atuid']]['avatar']?C('APP_URL_image').'/'.$userRes[$reply_data['reply_atuid']]['avatar']:'';
        $data['addReply']['reply_atuid_atnickname'] = $userRes[$reply_data['reply_atuid']]['nickname'];
        $data['addReply']['forum_id'] = $forum_data['forum_id'];
        $data['CustomEaseMessageType'] = 2;
        return $data;

    }

	//帖子详情
	public function detail()
	{
        $forum_id = I('post.forum_id');
		$token = I('post.token');
		if (!$forum_id || !$token) {
        	$this -> returnCode = 500;
            $this -> returnMsg = '参数错误';
            $this->returnData();
        }
        try{
            $userid = RedisCache::getInstance()->get($token);
            if (!$userid) {
                E("用户登录信息错误", 500);
            }
            $data = D('forum')->getOne(array('id'=>$forum_id,'forum_status'=>1),'id as forum_id,forum_uid,forum_content,forum_image,forum_voice,createtime,forum_voice_time,tid');
            if ($data) {
            	$data['createtime'] = date('Y-m-d H:i:s',$data['createtime']);
	            $arr = explode(',', $data['forum_image']);
	            foreach ($arr as $key => &$value) {
	            	$value = $value?C('APP_URL_image').'/'.$value:'';
	            }
	            $data['forum_image'] = implode(',', $arr);
                //一张图宽高
                if (count($arr) == 1) {
                    $whimg = getimagesize($data['forum_image']);
                    $data['img_w'] = $whimg[0];
                    $data['img_h'] = $whimg[1];
                }
                //点赞评论数
                $data['enjoy_num'] = D('forum_enjoy')->countNum(array('forum_id'=>$data['forum_id']));
                $data['reply_num'] = D('forum_reply')->countNum(array('forum_id'=>$data['forum_id'],'reply_status'=>1));
                //用户信息
                $userRes = D('member')->getOneById($data['forum_uid']);
                $data['avatar'] = $userRes['avatar']?C('APP_URL_image').'/'.$userRes['avatar']:'';
                $data['nickname'] = $userRes['nickname']?$userRes['nickname']:0;
                $data['sex'] = $userRes['sex']?$userRes['sex']:'';
                //点赞关注
                $attResIs = D('attention')->getOne(array('userid'=>$userid,'userided'=>$data['forum_uid']),'userid,userided');
                $enjoyResIs = D('forum_enjoy')->getOne(array('enjoy_uid'=>$userid,'forum_id'=>$data['forum_id']),'enjoy_uid,forum_id');
                $data['is_attention'] = $attResIs?1:0;
                $data['is_enjoy'] = $enjoyResIs?1:0;
                $data['forum_voice'] = $data['forum_voice']?C('APP_URL_image').'/'.$data['forum_voice']:'';
                //话题
                $topicRes = D('forum_topic')->where(['id'=>$data['tid']])->find();
                $data['topic_name'] = $topicRes['topic_name']?:'';

	            $this -> returnCode = 200;
        		$this -> returnData = $data;
            }else{
            	$this -> returnCode = 200;
        		$this -> returnData = [];
            }
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

	}

	//自己帖子列表
	public function selfforumList()
	{
		$token = I('post.token');
		$page = I('post.page');
		$pagenum = I('post.pagenum');
		if (!$token || !$page || !$pagenum) {
        	$this -> returnCode = 500;
            $this -> returnMsg = '参数错误';
            $this->returnData();
        }
        try{
        	$userid = RedisCache::getInstance()->get($token);
            if (!$userid) {
            	E("用户登录信息错误", 500);
            }
            
            $data['list'] = [];
            $data['page'] = $page;
            $data['pagenum'] = $pagenum;
            $data['pagecount'] = D('forum')->getAllcountNum($userid);
            $data['list'] = $this->getforumlist($page,$pagenum,$userid,'self',$tid);
    		$this -> returnCode = 200;
    		$this -> returnData = $data;
        	
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

	}

	//首页帖子列表
	public function forumList()
	{
		$token = I('post.token');
		$page = I('post.page');
        $pagenum = I('post.pagenum');
		$tid = I('post.tid');
		if (!$token || !$page || !$pagenum) {
        	$this -> returnCode = 500;
            $this -> returnMsg = '参数错误';
            $this->returnData();
        }
        try{
        	$userid = RedisCache::getInstance()->get($token);
            if (!$userid) {
            	E("用户登录信息错误", 500);
            }
            
            $data['list'] = [];
            $data['page'] = $page;
            $data['pagenum'] = $pagenum;
            $data['pagecount'] = D('forum')->getAllcountNum();
            $data['list'] = $this->getforumlist($page,$pagenum,$userid,'all',$tid);
    		$this -> returnCode = 200;
    		$this -> returnData = $data;
        	
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

	}

    //查询帖子方法
	public function getforumlist($page,$pagenum=20,$uid,$other='self',$tid=0)
	{
		$where = array();
		if ($other == 'self') {
			$where['forum_uid'] = $uid;
		}else{
    		//黑名单
    		$blackData = D('forum_black')->getlistall(array('black_uid'=>$uid),'toblack_uid');
    		if (!empty($blackData)) {
                foreach ($blackData as $key => $value) {
                    $blackStr .= $value['toblack_uid'].',';
                }
                $blackStr = rtrim($blackStr);
                $where['forum_uid'] = array('not in',$blackStr);    			
    		}
            if ($tid != 0) {
                $where['tid'] = $tid;
            }
        }
        
        $where['forum_status'] = 1;
		$field = 'id as forum_id,forum_uid,forum_content,forum_image,forum_voice,createtime,examined_time,forum_voice_time,tid';
		$start = ($page-1)*$pagenum;
		$limit = $start.','.$pagenum;
        $data = D('forum')->getlist($where,$field,$limit,'examined_time desc');
        if (empty($data)) {
        	return array();
        }

        $disStr = '';
        $uidStr = '';
    	foreach ($data as $key => $value) {
            $disStr .= $value['forum_id'].',';
            $uidStr .= $value['forum_uid'].',';
    		$data[$key]['createtime'] = date('Y-m-d H:i:s',$value['createtime']);
            if (!empty($value['forum_image'])) {
                $imageArr = explode(',', $value['forum_image']);
                foreach ($imageArr as $k => &$v) {
                    $v = $v?C('APP_URL_image').'/'.$v:'';
                }
                $data[$key]['forum_image'] = implode(',', $imageArr);
                //一张图宽高
                if (count($imageArr) == 1) {
                    $whimg = getimagesize($data[$key]['forum_image']);
                    $data[$key]['img_w'] = $whimg[0];
                    $data[$key]['img_h'] = $whimg[1];
                }
            }
    	}

        $disStr = trim($disStr,',');
        $uidStr = trim($uidStr,',');
        //查询点赞评论
        $enjoyRes = D('forum_enjoy')->getAllNum($disStr);
        $replyRes = D('forum_reply')->getAllNum($disStr);
        //用户信息
        $uidRes = D('member')->getUserByInid(array('id'=>array('in',$uidStr)),'id as uid,avatar,nickname,sex');
        //关注点赞
        $attResIs = D('attention')->getlistAll(array('userid'=>$uid),'userid,userided');
        $enjoyResIs = D('forum_enjoy')->getlistAll(array('enjoy_uid'=>$uid,'forum_id'=>array('in',$disStr)),'enjoy_uid,forum_id');
        //话题
        $tag = D('forum_topic')->select();
        $TopicRes = array_column($tag,'topic_name','id');
        $tagRes = [];
        foreach ($tag as $key => $value) {
                $tagRes[$value['id']] = $value['pid'];
        }

        foreach ($data as $key => $value) {
            $data[$key]['enjoy_num'] = $enjoyRes[$value['forum_id']]?$enjoyRes[$value['forum_id']]:0;
            $data[$key]['reply_num'] = $replyRes[$value['forum_id']]?$replyRes[$value['forum_id']]:0;
            $data[$key]['avatar'] = $uidRes[$value['forum_uid']]['avatar']?C('APP_URL_image').'/'.$uidRes[$value['forum_uid']]['avatar']:'';
            $data[$key]['sex'] = $uidRes[$value['forum_uid']]['sex']?$uidRes[$value['forum_uid']]['sex']:'';
            $data[$key]['nickname'] = $uidRes[$value['forum_uid']]['nickname']?$uidRes[$value['forum_uid']]['nickname']:'';
            $data[$key]['is_attention'] = $attResIs[$value['forum_uid']]?1:0;
            $data[$key]['is_enjoy'] = $enjoyResIs[$value['forum_id']]?1:0;
            $data[$key]['forum_voice'] = $value['forum_voice']?C('APP_URL_image').'/'.$value['forum_voice']:'';
            $data[$key]['topic_name'] = $TopicRes[$value['tid']]?:'';
            $data[$key]['tag_name'] = $TopicRes[$tagRes[$value['tid']]]?:'';
            if ($other == 'all') {
                $data[$key]['createtime'] = date('Y-m-d H:i:s',$value['examined_time']);
            }
        }
        return $data;
	}

	//发帖
	public function addforum()
	{
        if (C('FORUM')) {
            $this -> returnCode = 500;
            $this -> returnMsg = '系统升级中';
            $this->returnData();
        }
		$token = I('post.token');
		$content = $_POST['content'];
        $content = trim($content);
		$image = I('post.image')?I('post.image'):'';
        $voice = I('post.voice')?I('post.voice'):'';
        $forum_voice_time = I('post.forum_voice_time')?I('post.forum_voice_time'):0;
		$topic = I('post.tid')?I('post.tid'):16; //默认此刻
		if ($content == '' && $image == '' && $voice == '') {
        	$this -> returnCode = 500;
            $this -> returnMsg = '参数错误';
            $this->returnData();
        }
        if (!empty($image)) {
            $image = trim($image);
            $imageArr = explode(',', $image);
            if (is_array($imageArr)) {
                foreach ($imageArr as $key => $value) {
                    if (empty($value)) {
                        $this -> returnCode = 500;
                        $this -> returnMsg = '图片参数错误';
                        $this->returnData();
                        break;
                    }
                }
            }else{
                $this -> returnCode = 500;
                $this -> returnMsg = '图片参数错误';
                $this->returnData();
            }
        }
        if ($forum_voice_time > 60) {
            $this -> returnCode = 500;
            $this -> returnMsg = '语音时长超限';
            $this->returnData();
        }

        try{
        	$userid = RedisCache::getInstance()->get($token);
            if (!$userid) {
            	E("用户登录信息错误", 500);
            }
            $time = time();

            $addParam = array('forum_uid'=>$userid,'forum_content'=>$content,'forum_image'=>$image,'forum_voice'=>$voice,'createtime'=>$time,'updatetime'=>$time,'forum_voice_time'=>$forum_voice_time,'tid'=>$topic,'forum_status'=>3);

        	$data = D('forum')->addData($addParam);

        	if ($data) {
                $tag = D('forum_topic')->where(['id'=>$topic])->find();
                if (!empty($tag['pid'])) {
                    RedisCache::getInstance()->getRedis()->ZINCRBY($this->tagKey,1,$tag['pid']);
                    RedisCache::getInstance()->getRedis()->INCRBY($this->topicKey.$topic,1);
                }
                // $msgkey = $userid.'_'.$data;
                // RedisCache::getInstance()->getRedis()->LPUSH('forum_msg',$msgkey);
        		$this -> returnCode = 200;
        		$this -> returnMsg = '发表成功,请等待审核通过';
        	}else{
        		$this -> returnCode = 500;
        		$this -> returnMsg = '发表失败';
        	}
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();


	}

	//自己删帖
	public function delforum()
	{
		$token = I('post.token');
		$forum_id = I('post.forum_id');
		if (!$token || !$forum_id) {
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
            $res = D('forum')->getOne(array('id'=>$forum_id,'forum_uid'=>$userid),'id,forum_uid,forum_status,tid');
            if (empty($res)) {
                E("删除动态不存在", 500);
            }
            if ($res['forum_status'] == 4) {
                E("动态已经删除", 500);
            }

        	$delParam = array('forum_selfdeluid'=>$userid,'forum_selfdeltime'=>$time,'forum_status'=>4,'updatetime'=>$time);
        	$data = D('forum')->updateData(array('id'=>$res['id']),$delParam);
        	if ($data) {
                $tag = D('forum_topic')->where(['id'=>$res['tid']])->find();
                RedisCache::getInstance()->getRedis()->ZINCRBY($this->tagKey,-1,$tag['pid']);
        		$this -> returnCode = 200;
        		$this -> returnMsg = '删除成功';
        	}else{
        		$this -> returnCode = 500;
        		$this -> returnMsg = '删除失败';
        	}
            
        }catch(Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();
	}

	



}


?>