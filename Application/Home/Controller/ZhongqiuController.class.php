<?php
namespace Home\Controller;
use Think\Controller;
use Common\Util\RedisCache;
use Think\Log;

class ZhongqiuController extends Controller {

	public function zqph()
	{
		$img = C('APP_URL_image');
		$selfUid = 0;
        $list = array();
        $info = array();
    	$token = $_REQUEST['mtoken'];
    	$selfUid = RedisCache::getInstance()->get($token);
		$Coin = D('Api/Coindetail');
		$where['addtime'] = array(array('egt','2019-09-13 00:00:00'),array('lt','2019-09-16 00:00:00'));
		$where['action'] = 'sendgift';
		$where['giftid'] = array('in','307 ,308');
		// $where['touid'] = array('not in','12');
		$result = $Coin->getzhongqiu($where);
		$data = [];
		if (empty($result)) {
			$this->assign('hava_res',1);
			$this->assign('imgurl',$img);
            // $this->display();
		}else{
			foreach ($result as $key => $value) {
				//判断最后一天减半
				$data[$value['touid']]['uid'] = $value['uid'];
				$data[$value['touid']]['touid'] = $value['touid'];
				$data[$value['touid']]['avatar'] = $value['avatar']?C('APP_URL_image').'/'.$value['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
				$data[$value['touid']]['nickname'] = $value['nickname'];
				if (strtotime($value['addtime']) >= strtotime('2019-09-15 00:00:00')) {
					$data[$value['touid']]['coin'] += $value['coin'] / 2;
				}else{
					$data[$value['touid']]['coin'] += $value['coin'];
				}
			}
			$coinTmp = array_column($data,'coin');
			$touidTmp = array_column($data,'touid');
			array_multisort($coinTmp,SORT_DESC,$touidTmp,SORT_ASC,$data);

			foreach ($data as $key => $value) {
				$data[$key]['coin'] = floor($value['coin']);
				if ($key < 9) {
                    $num = '0'.($key + 1);
                }else{
                    $num = $key + 1;
                }
                $data[$key]['num'] = $num;
	        	//判断自己rank
	        	if ($selfUid == $value['touid']) {
	        		$info = $value;
                    $info['num'] = $num;
	        	}
			}
		    $this->assign('result',$data);
	        $this->assign('hava_res',2);
	        $this->assign('imgurl',$img);
	        
		}
		if (empty($info)) {
        	if ($selfUid > 0) {
        		$Member = D('Api/Member');
        		$mydata = $Member->getOneById($selfUid);
	    		$info['num'] = '未上榜';
	    		$info['avatar'] = $mydata['avatar']?C('APP_URL_image').'/'.$mydata['avatar']:C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
	    		$info['nickname'] = $mydata['nickname'];
	    		$info['coin'] = 0;
        	}else{
        		$info['num'] = '未上榜';
		   		$info['avatar'] = C('APP_URL_image').'/'.'Public/Uploads/image/logo.png';
		   		$info['nickname'] = '暂无';
		   		$info['coin'] = 0;
        	}
	    }
	    $this->assign('info',$info);
		$this->display();

	}








}
