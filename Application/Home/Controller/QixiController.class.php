<?php
namespace Home\Controller;
use Think\Controller;
use Common\Util\RedisCache;
use Think\Log;

class QixiController extends Controller {

	public function qxrank(){
		$selfUid = 0;
		$hava_res = 1;
        $list = array();
        $info = array();
    	$token = $_REQUEST['mtoken'];
    	$selfUid = RedisCache::getInstance()->get($token);
		$Coin = D('Api/Coindetail');
		$Member = D('Api/Member');
		$where['addtime'] = array(array('egt','2019-08-06 00:00:00'),array('lt','2019-08-09 00:00:00'));
		// $where['addtime'] = array(array('egt','2019-08-06 02:05:00'),array('lt','2019-08-06 03:05:00'));
		$where['action'] = 'sendgift';
		// $where['giftid'] = array('in','1,2');
		$where['giftid'] = array('in','248 ,249');
$where['touid'] = array('not in','1000009');
        // $limit = 10;
		$group = 'touid';
        $result = $Coin->getQixi($where,$group);
        if (empty($result)) {
        	$this->assign('hava_res',$hava_res);
            $this->display();
        }else{
	        $whereLike['addtime'] = array(array('egt','2019-08-06 00:00:00'),array('lt','2019-08-09 00:00:00'));
	        // $whereLike['addtime'] = array(array('egt','2019-08-06 02:05:00'),array('lt','2019-08-06 03:05:00'));
			$whereLike['action'] = 'sendgift';
			// $whereLike['giftid'] = array('in','1,2');
			$whereLike['giftid'] = array('in','248,249');
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
	    		$info['num'] = '0';
	    		$info['avatar'] = $mydata['avatar']?C('APP_URL_image').'/'.$mydata['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
	    		$info['nickname'] = $mydata['nickname'];
	    		$info['coin'] = 0;
        	}else{
        		$info['num'] = '0';
		   		$info['avatar'] = C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
		   		$info['nickname'] = '暂无';
		   		$info['coin'] = 0;
        	}
	    }
	    // else{
	    // 	$info['num'] = 0;
	   	// 	$info['avatar'] = C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
	   	// 	$info['nickname'] = '暂无';
	   	// 	$info['coin'] = 0;
	    // }
//         echo "<pre>";
//         var_dump($result,$info);
        $this->assign('result',$result);
        $this->assign('info',$info);
        $this->assign('hava_res',$hava_res);
        $this->display();

    }

   public function qxrank_bak(){
   	$top = array();
       $list = array();
       $info = array();
   	$token = $_REQUEST['token'];
   	$selfUid = RedisCache::getInstance()->get($token);

		$Coin = D('Api/Coindetail');
		$Member = D('Api/Member');
		// $where['addtime'] = array(array('egt','2019-08-06 00:00:00'),array('lt','2019-08-08 00:00:00'));
		$where['addtime'] = array(array('egt','2019-07-31 16:37:06'),array('lt','2019-07-31 16:37:20'));
		$where['action'] = 'sendgift';
		// $where['giftid'] = array('in','1,2');
		$where['giftid'] = array('in','256,257');
		$group = 'touid';
       $result = $Coin->getQixi($where,$group);
       if (empty($result)) {
       	for ($i=0; $i < 3; $i++) {
       		$result[$i]['nickname'] = '暂无';
       		$result[$i]['num'] = '0'.($i+1);
       		$result[$i]['avatar'] = C('APP_URL_image_web').'/'.'qixi/test'.($i+1).'.jpg';
       		$result[$i]['likeimg'] = [];
       	}
       }else{
	        // $whereLike['addtime'] = array(array('egt','2019-08-06 00:00:00'),array('lt','2019-08-08 00:00:00'));
	        $whereLike['addtime'] = array(array('egt','2019-07-31 16:37:06'),array('lt','2019-07-31 16:37:20'));
			$whereLike['action'] = 'sendgift';
			// $whereLike['giftid'] = array('in','1,2');
			$whereLike['giftid'] = array('in','256,257');
			$field = 'sum(coin) coin,m.avatar';
			$limit = 3;
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
	        		$info = substr($value['nickname'] , 0 , 15);
	        		$info['avatar'] = $value['avatar']?C('APP_URL_image').'/'.$value['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
	        		$info['num'] = $num;
	        	}

	        	$result[$key]['avatar'] = $value['avatar']?C('APP_URL_image').'/'.$value['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
	        	$result[$key]['num'] = $num;

	        	$whereLike['touid'] = $value['touid'];
	        	if ($key < 3) {
	        		$tmp = $Coin->getQixiLike($field,$whereLike,$groupLike,$limit);
	        		echo $Coin->getLastSql();die;
		        	foreach ($tmp as $k => $v) {
		        		$result[$key]['likeimg'][$k]['avatar'] = $v['avatar']?C('APP_URL_image').'/'.$v['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
		        		$result[$key]['likeimg'][$k]['coin'] = floor($v['coin']);
		        	}
	        	}
	        }
	        if (count($result) < 3) {
				for ($i=count($result); $i < 3; $i++) {
					$result[$i]['nickname'] = '暂无';
					$result[$i]['num'] = '0'.($i+1);
					$result[$i]['avatar'] = C('APP_URL_image_web').'/'.'qixi/test'.($i+1).'.jpg';
					$result[$i]['likeimg'] = [];
				}
			}
       }

       if (empty($info) && $selfUid) {
       	$mydata = $Member->getOneById($selfUid);
       	if (!empty($mydata)) {
       		$info['num'] = '未上榜';
	   		$info['avatar'] = C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
	   		$info['nickname'] = '';
	   		$info['coin'] = 0;
       	}else{
       		$info['num'] = '未上榜';
	   		$info['avatar'] = C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
	   		$info['nickname'] = '';
	   		$info['coin'] = 0;
       	}
   		
	    }

       foreach ($result as $key => $value) {
       	if ($key < 3) {
       		$top[] = $value;
       	}else{
       		if ($key > 9) {
       			break;
       		}
       		$list[] = $value;
       	}
       }
       echo "<pre>";
       var_dump($result);
		echo "</pre>";die();
       $this->assign('top',$top);
       $this->assign('list',$list);
       $this->assign('info',$info);
       $this->display();
       // print_r($result);
   }

   public function qxranklike()
   {
   	$top = array();
   	$info = array();
   	$list = array();
   	$touid = $_REQUEST['touid'];
   	$uid = $_REQUEST['uid'];
   	if (empty($touid)) {
   		echo "<script>window.history.back(-1);</script>";
   		exit;
   	}

   	$Coin = D('Api/Coindetail');
   	// $where['addtime'] = array(array('egt','2019-08-06 00:00:00'),array('lt','2019-08-08 00:00:00'));
   	$where['addtime'] = array(array('egt','2019-07-31 16:37:06'),array('lt','2019-07-31 16:37:20'));
		$where['action'] = 'sendgift';
		// $where['giftid'] = array('in','1,2');
		$where['giftid'] = array('in','256,257');
		$where['touid'] = $touid;
		$group = 'uid';
		$result = $Coin->getQixi($where,$group);

		if (empty($result)) {
			for ($i=0; $i < 3; $i++) {
       		$result[$i]['nickname'] = '暂无';
       		$result[$i]['num'] = '0'.($i+1);
       		$result[$i]['avatar'] = C('APP_URL_image_web').'/'.'qixi/test'.($i+1).'.jpg';
       	}
		} else {
			foreach ($result as $key => $value) {
				if ($key < 10) {
	        		$num = '0'.($key + 1);
	        	}else{
	        		$num = $key;
	        	}
	        	//判断自己rank
	        	if ($uid == $value['uid']) {
	        		$info = $value;
	        		$info['avatar'] = $value['avatar']?C('APP_URL_image').'/'.$value['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
	        		$info['num'] = $num;
	        	}

	        	$result[$key]['avatar'] = $value['avatar']?C('APP_URL_image').'/'.$value['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
	        	$result[$key]['num'] = '0'.($key + 1);

	        }
	        if (count($result) < 3) {
				for ($i=count($result); $i < 3; $i++) {
					$result[$i]['nickname'] = '暂无';
					$result[$i]['num'] = '0'.($i+1);
					$result[$i]['avatar'] = C('APP_URL_image_web').'/'.'qixi/test'.($i+1).'.jpg';
				}
			}

		}

		if (empty($info)) {
			$Member = D('Api/Member');
   		$mydata = $Member->getOneById($uid);
   		$info['num'] = 0;
   		$info['avatar'] = $mydata['avatar']?C('APP_URL_image').'/'.$mydata['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
   		$info['nickname'] = $mydata['nickname']?$mydata['nickname']:'';
   		$info['coin'] = 0;
		}

		foreach ($result as $key => $value) {
       	if ($key < 3) {
       		$top[] = $value;
       	}else{
       		if ($key > 5) {
       			break;
       		}
       		$list[] = $value;
       	}
       }


       $imgurl = C('APP_URL_image_web');
       $str_head = "<div class='covers_quit'>
			<img id='closeed' src='".$imgurl."/qixi/images/pop_up_quit_icon.png' alt='pop_up_quit_icon' style='margin-top: -34%;margin-left: 96%;' />
		</div>
			<div style='margin-bottom: 5vw;'>
			<div style='margin-top: 20vw;height: 40vw;'>
				<img src='".$imgurl."/qixi/images/1234.png' class='covers-img'>
				<div class='task' style='float: left;margin-top: -7%;width: 17vw;margin-left: 16%;height: 31vw;position: relative;'>
						<div ><img class='covers-hean-img' src='".$top[2]['avatar']."'></div>
						<div class='covers-hean-font'>
							<p >".$top[2]['nickname']."</p>
						</div>
				</div>
				<div class='task2' style='float: left;margin-top: -12%;width: 17vw;margin-left: 4%;height: 31vw;position: relative;'>
					<volist name='top' id='topinfo' offset='2' length='1'>
						<img class='covers-hean-img' src='".$top[0]['avatar']."'>
						<div class='covers-hean-font'>
							<p >".$top[0]['nickname']."</p>
						</div>
					</volist>
				</div>
				<div class='task3' style='float: left;margin-top: -7%;width: 17vw;margin-left: 4%;height: 31vw;position: relative;'>
					<volist name='top' id='topinfo2' offset='1' length='1'>
						<img  class='covers-hean-img' src='".$top[1]['avatar']."'>
						<div class='covers-hean-font'>
							<p >".$top[1]['nickname']."</p>
						</div>
					</volist>
				</div>
			</div>
		</div>";
		$str = '';
		foreach ($list as $key => $value) {
			$str .= "<div class='little-task' >
				<div class='covers-list-number'>".$value['num']."</div>
				<img class='covers-list-img' src='".$value['avatar']."'>
				<div class='covers-list-name'>".$value['nickname']."</div>
				<div class='covers-list-integral'>".$value['coin']."</div>
				<div class='covers-list-integrals'>积分</div>
			</div>";
		}


		$str_foot = "<div class='covers-my' >
			<div class='covers-list-number'>".$info['num']."</div>
			<img class='covers-list-img' src='".$info['avatar']."'>
			<div class='covers-list-name'>".$info['nickname']."</div>
			<div class='covers-list-integral'>".$info['coin']."</div>
			<div class='covers-list-integrals'>积分</div>
		</div>";
		$script = "<script type='text/javascript'>
	  				$('#closeed').click(function(){
	  				$('html,body').toggleClass('noscroll');
				  	$('#tangcenghtml').hide();
				  });</script>";
		echo $str_head.$str.$str_foot.$script;
       // $this->assign('top',$top);
       // $this->assign('list',$list);
       // $this->assign('info',$info);
       // $this->display();

   }



}
