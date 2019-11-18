<?php
namespace Home\Controller;
use Think\Controller;
use Common\Util\RedisCache;
use Think\Log;

class LastweekController extends Controller {
    /**
     * 上周明星榜数据
     */
	public function lastweek(){
		$selfUid = 0;
		$hava_res = 1;
        $list = array();
        $info = array();
    	$token = $_REQUEST['mtoken'];
    	$selfUid = RedisCache::getInstance()->get($token);
		$Coin = D('Api/Coindetail');
		$Member = D('Api/Member');
        //查询数据(统计上周的数据)
        $time=date("w",time( ));
        $array = ["周日","周一","周二","周三","周四","周五","周六"];
        if($array[$time] == "周日"){
            $beginLastweek = mktime(0,0,0,date('m'),date('d')-date('w')+1-14,date('Y'));
            $endLastweek = mktime(23,59,59,date('m'),date('d')-date('w')+7-14,date('Y'));
        }else{
            $beginLastweek = mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));
            $endLastweek = mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'));
        }
        $beginLastweek = date('Y-m-d',$beginLastweek)." 00:00:00";
        $endLastweek = date('Y-m-d',$endLastweek)." 23:59:59";
        //统计上周用户明星值
        $where = array(
            "addtime >= '".$beginLastweek."' and addtime <= '".$endLastweek."'",
        );
		$where['action'] = 'sendgift';
		$where['giftid'] = array('in','286,303,304,306,309,310');
        $where['touid'] = array('not in','1000009,1012582');
        // $limit = 10;
		$group = 'touid';
        $result = $Coin->getQixi($where,$group);
//        echo $Coin->getLastSql();
//        echo "<pre>";
//        var_dump($result);
//        echo "</pre>";die();
        if (empty($result)) {
        	$this->assign('hava_res',$hava_res);
            $this->display();
        }else{
            $whereLike = array(
                "addtime >= '".$beginLastweek."' and addtime <= '".$endLastweek."'",
            );
			$whereLike['action'] = 'sendgift';
			// $whereLike['giftid'] = array('in','1,2');
			$whereLike['giftid'] = array('in','286,303,304,306,309,310');
			$field = 'sum(coin) coin,m.avatar,m.nickname';
			$groupLike ='uid';
	        foreach ($result as $key => $value) {
                if ($key < 9) {
                    $num = '0'.($key + 1);
                }else{
                    $num = $key+1;
                }
	        	//判断自己rank
	        	if ($selfUid == $value['touid']) {
	        		$info = $value;
	        		$info['coin'] = floor($info['coin']);
				    $info['nickname'] = substr($value['nickname'] , 0 , 15);
	        		$info['avatar'] = $value['avatar']?C('APP_URL_image').'/'.$value['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
                    $info['num'] = $num;
	        	}
	        	$result[$key]['avatar'] = $value['avatar']?C('APP_URL_image').'/'.$value['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
	        	$result[$key]['coin'] = floor($value['coin']);
	        	$whereLike['touid'] = $value['touid'];
                $tmp = $Coin->getQixiLike($field,$whereLike,$groupLike);
                $result[$key]['likeimg']['avatar'] = $tmp['avatar']?C('APP_URL_image').'/'.$tmp['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
                $result[$key]['likeimg']['coin'] = floor($tmp['coin']);
                $result[$key]['likeimg']['nickname'] = $tmp['nickname'];
                $result[$key]['num'] = $num;
	        }
        }
        if (empty($info)) {
        	if ($selfUid > 0) {
        		$mydata = $Member->getOneById($selfUid);
	    		$info['num'] = "未上榜";
	    		$info['avatar'] = $mydata['avatar']?C('APP_URL_image').'/'.$mydata['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
	    		$info['nickname'] = $mydata['nickname'];
	    		$info['coin'] = 0;
        	}else{
        		$info['num'] = "未上榜";
		   		$info['avatar'] = C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
		   		$info['nickname'] = '暂无';
		   		$info['coin'] = 0;
        	}
	    }
        $this->assign('result',$result);
        $this->assign('info',$info);
        $this->assign('hava_res',$hava_res);
        $this->display();

    }

    /**
     * 本周明星榜
     */
    public function startlist(){
        $selfUid = 0;
        $hava_res = 1;
        $list = array();
        $info = array();
        $token = $_REQUEST['mtoken'];
        $selfUid = RedisCache::getInstance()->get($token);
        $Coin = D('Api/Coindetail');
        $Member = D('Api/Member');
        //周榜开始
        //查询数据(统计上周的数据)
        $time=date("w",time( ));
        $array = ["周日","周一","周二","周三","周四","周五","周六"];
        if($array[$time] == "周日"){
            $beginLastweek = mktime(0,0,0,date('m'),date('d')-date('w')+1-14,date('Y'));
            $endLastweek = mktime(23,59,59,date('m'),date('d')-date('w')+7-14,date('Y'));
        }else{
            $beginLastweek = mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));
            $endLastweek = mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'));
        }
        $beginLastweek = date('Y-m-d',$beginLastweek)." 00:00:00";
        $endLastweek = date('Y-m-d',$endLastweek)." 23:59:59";
        //统计上周用户明星值
        $whereweek = array(
            "addtime >= '".$beginLastweek."' and addtime <= '".$endLastweek."'",
        );
        $whereweek['action'] = 'sendgift';
        
            $whereweek['giftid'] = array('in','286,303,304,306,309,310');
        

        $whereweek['touid'] = array('not in','1000009,1012582');
        // $limit = 10;
        $group = 'touid';
        $week_result = $Coin->getQixi($whereweek,$group);
//        echo $Coin->getLastSql();die();
        $weekresult = array_slice($week_result, 0, 3);
        foreach($weekresult as $key=>$value){
            $weekresult[$key]['avatar'] = $value['avatar']?C('APP_URL_image').'/'.$value['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
            $weekresult[$key]['coin'] = floor($value['coin']);
        }
//        echo "<pre>";
//        var_dump($weekresult);
//        echo "</pre>";die();
        //周榜结束
//        $where['addtime'] = array(array('egt','2019-08-17 00:00:00'),array('lt','2019-08-19 00:00:00'));
        //本周开始
        $weekone = date('Y-m-d',(time()-((date('w',time())==0?7:date('w',time()))-1)*24*3600))." 00:00:00";
        $weekseven =  date('Y-m-d',(time()+(7-(date('w',time())==0?7:date('w',time())))*24*3600))." 23:59:59";
        $where = array(
            "addtime >= '".$weekone."' and addtime <= '".$weekseven."'",
        );
        //本周结束
        $where['action'] = 'sendgift';
        // $where['giftid'] = array('in','1,2');
        // 判断不显示
        if (time() < 1569772800) {
            $where['giftid'] = array('in','286,303,304,306,309,310');
        }else{
            $where['giftid'] = array('in','-1');
        }
        // 判断不显示end
        
        $where['touid'] = array('not in','1000009');
        // $limit = 10;
        $group = 'touid';
        $result = $Coin->getQixi($where,$group);
//        echo $Coin->getLastSql();die();
        if (empty($result)) {
            $this->assign('hava_res',$hava_res);
            $this->display();
        }else{
//            $whereLike['addtime'] = array(array('egt','2019-08-17 00:00:00'),array('lt','2019-08-19 00:00:00'));
            $whereLike = array(
                "addtime >= '".$weekone."' and addtime <= '".$weekseven."'",
            );
//            $whereLike['addtime'] = $where;
            // $whereLike['addtime'] = array(array('egt','2019-08-06 02:05:00'),array('lt','2019-08-06 03:05:00'));
            $whereLike['action'] = 'sendgift';
            // $whereLike['giftid'] = array('in','1,2');
            $whereLike['giftid'] = array('in','286,303,304,306,309,310');
            $field = 'sum(coin) coin,m.avatar,m.nickname';
            $groupLike ='uid';
            foreach ($result as $key => $value) {
                if ($key < 9) {
                    $num = '0'.($key + 1);
                }else{
                    $num = $key+1;
                }
                //判断自己rank
                if ($selfUid == $value['touid']) {
                    $info = $value;
                    $info['nickname'] = substr($value['nickname'] , 0 , 15);
                    $info['avatar'] = $value['avatar']?C('APP_URL_image').'/'.$value['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
                    $info['num'] = $num;
                }
                $result[$key]['avatar'] = $value['avatar']?C('APP_URL_image').'/'.$value['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
                $result[$key]['coin'] = floor($value['coin']);
                $whereLike['touid'] = $value['touid'];
                $tmp = $Coin->getQixiLike($field,$whereLike,$groupLike);
//                echo $Coin->getLastSql();die();
                $result[$key]['likeimg']['avatar'] = $tmp['avatar']?C('APP_URL_image').'/'.$tmp['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
                $result[$key]['likeimg']['coin'] = floor($tmp['coin']);
                $result[$key]['likeimg']['nickname'] = $tmp['nickname'];
                $result[$key]['num'] = $num;
            }
        }
        // echo "<pre>";
        // var_dump($result);
        if (empty($info)) {
            if ($selfUid > 0) {
                $mydata = $Member->getOneById($selfUid);
                $info['num'] = "未上榜";
                $info['avatar'] = $mydata['avatar']?C('APP_URL_image').'/'.$mydata['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
                $info['nickname'] = $mydata['nickname'];
                $info['coin'] = 0;
            }else{
                $info['num'] = "未上榜";
                $info['avatar'] = C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
                $info['nickname'] = '暂无';
                $info['coin'] = 0;
            }
        }
        $appurl = C("APP_URLS");
        $this->assign("weekresult",$weekresult);
        $this->assign('result',$result);
        $this->assign('info',$info);
        $this->assign('hava_res',$hava_res);
        $this->assign("token",$token);
        $this->assign("appurl",$appurl);
        $this->display();

    }



}
