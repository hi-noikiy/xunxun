<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\Easemob;
use Think\Exception;
use Think\Log;
/**
 * 支付接口统一类.
 * Class OrderAction
 */
class OrderController extends BaseController {

	protected $default_msg = array(
		"请求地址"                       => "/v1/Order/",
		"统一下单方法"                   => "pay",
		"统一回调处理方法，给第三方回调使用" => "updateOrder"
	);

	protected static $list ; // 充值比例

	protected static $AliPay;

	protected static $WeiXin;

	protected static $ApplePay;

	protected static $ThirdPay;
	/**
	 * 初始化配置信息
	 * OrderAction constructor.
	 */
	public function __construct()
	{
		// 实现父类的构造
		parent::__construct();
		// 注入三个支付类.
		self::$AliPay   = new AliPayController();
		self::$WeiXin   = new WeiXinController();
//		self::$ApplePay = new ApplepayController();
//		self::$ThirdPay = new ThirdPayController();
		// 获取支付比例.
		self::$list =D('charge')->getChargeList();
		
	}
        
	/**
	 * 支付接口 开关，方便上架AppStore
	 */
	public function paySwitch()
	{
		if (!C('PAYMENT.SWITCH')) {
			$this->responseError('off');
		}

		$this->responseSuccess('on');
	}
	/**
	 * 充值列表接口
	 */
	public function chargelist($token)
	{
	    try{
	        $field="id as chargeid ,rmb,diamond,present,chargemsg,vipgift,coinimg";
	        $charge_list=D('charge')->getlists($field);
	        foreach($charge_list as $k=>$v){
                  $charge_list[$k]['coinimg']=C('APP_URL').$v['coinimg'];
	        
	        }
	     //   var_dump($charge_list);die;
	        $result = [
	            "charge_list"=>$charge_list,	           
	        ];
	    $this -> returnCode = 200;
	    $this -> returnMsg = "操作成功";
	    $this -> returnData=$result;
	    }catch (Exception $e){
	        $this -> returnCode = $e ->getCode();
	        $this -> returnMsg = $e ->getMessage();
	    }
	    $this->returnData();
	}
	
		/**
	 * MB 明细接口
	 */
	public function mbdetails($token,$page)
	{
	    try{
	        /**********************消耗M豆部分****************************************/
	        $userid = RedisCache::getInstance()->get($token);
	        $page=$_REQUEST['page'];
	        //用户消耗m豆的条件查询$where
	        $where['uid']=$userid;
	        $where['action'] = array('in','vip,sendgift,guard');//
	        $mdcount= D('coindetail')->record($where);
	      //  var_dump($mdcount);die;
	        $wheres['action'] = array('in','charges,Modes');//
	        $wheres['uid']=$userid;
	        $mdcounts= D('beandetail')->records($wheres);
	       // var_dump($mdcounts);die;
	        $login_userinfo=D('member')->getOneByIdField($userid,'nickname');
	        $size = 20;
	        $pageNum = empty($size)?20:$size;
	        $page = empty($page)?1:$page;
	        $count=$mdcount+$mdcounts;
	        // var_dump($count);die;
	        // 总页数.
	        $totalPage = ceil($count/$pageNum);
	        // 页数信息.
	        $pageInfo = array("page" => $page, "pageNum"=>$pageNum, "totalPage" => $totalPage);
	        $limit = ($page-1) * $size . "," . $size;
	        $field="action,coin,addtime";
	        $wheress['uid']=$userid;
	        $wheress['action']=array('in','vip,sendgift,guard');
	        $mdrecord= D('coindetail')->mbdetails($userid,$wheress,$limit);	
	      //  var_dump($mdrecord);die;
	        foreach($mdrecord as $k=>$v){
	            if($v['action']=='vip'){//消耗M豆
	                $mdrecord[$k]['content']=$login_userinfo."购买vip";
	                $mdrecord[$k]['type']="expend";
	            }elseif($v['action']=="sendgift"){//消耗M豆
	                $mdrecord[$k]['content']="礼物打赏";
	                $mdrecord[$k]['type']="expend";
	            }elseif($v['action']=='guard'){//消耗M豆
	                $mdrecord[$k]['content']=$login_userinfo."购买守护";
	                $mdrecord[$k]['type']="expend";
	            }elseif($v['action']=='charges'){//增加M豆
	                $mdrecord[$k]['content']="充值到钱包";
	                $mdrecord[$k]['type']="incre";
	            }elseif($v['action']=="Modes"){//增加M豆
	                $mdrecord[$k]['content']="钻石兑换M豆";
	                $mdrecord[$k]['type']="incre";
	            }
	        }	
	        /*elseif($v['action']=="get_gift"){//收到礼物增加钻石 移除
	                unset($mdrecord[$k]);
	            }elseif($v['action']=="changes"){//减少钻石(钻石换M豆  减少钻石)
	                unset($mdrecord[$k]);
	            }elseif($v['action']=="cash"){//钻石提现 减少钻石
	                unset($mdrecord[$k]);
	            }*/
//	        $mdrecords=array_values($mdrecord);
//	        $money_record = $this->sortByFieldASC($mdrecords,'addtime');
            $money_record=array_values($mdrecord);
//            $money_record = $this->sortByFieldASC($mdrecords,'addtime');
            if($money_record==null){
	            $result=[
	                'money_record'=>[],
	                'pageInfo'=>$pageInfo,
	            ];
	        }else{
	            $result=[
	                'money_record'=>$money_record,
	                'pageInfo'=>$pageInfo,
	            ];
	        }
	        $this -> returnCode = 200;
	        $this -> returnMsg = "操作成功";
	        $this -> returnData=$result;
	    }catch (Exception $e){
	        $this -> returnCode = $e ->getCode();
	        $this -> returnMsg = $e ->getMessage();
	    }
	    $this->returnData();
	}
	
	
		/**钻石明细接口**/
	public function diamondtails($token,$page)
	{
	    try{
	        /**********************消耗M豆部分****************************************/
	        $userid = RedisCache::getInstance()->get($token);
	       $page=$_REQUEST['page'];
	        //用户消耗m豆的条件查询$where
	        $where['uid']=$userid;
	        $where['action'] = array('in','cash,changes');//
	        $mdcount= D('coindetail')->record($where);
	        //  var_dump($mdcount);die;
	        $wheres['action'] = array('in','get_gift');//
	        $wheres['uid']=$userid;
	        $mdcounts= D('beandetail')->records($wheres);
	        // var_dump($mdcounts);die;
	        $login_userinfo=D('member')->getOneByIdField($userid,'nickname');
	        $size = 20;
	        $pageNum = empty($size)?20:$size;
	        $page = empty($page)?1:$page;
	        $count=$mdcount+$mdcounts;
	        // var_dump($count);die;
	        // 总页数.
	        $totalPage = ceil($count/$pageNum);
	        // 页数信息.
	        $pageInfo = array("page" => $page, "pageNum"=>$pageNum, "totalPage" => $totalPage);
	        $limit = ($page-1) * $size . "," . $size;
	        $field="action,coin,addtime";
	        $wheress['uid']=$userid;
	        $wheress['action']=array('in','cash,changes');
	        $mdrecord= D('coindetail')->diamonddetails($userid,$wheress,$limit);
	    //    var_dump($mdrecord);die;
	        foreach($mdrecord as $k=>$v){
	            if($v['action']=='cash'){//消耗钻石
	                $mdrecord[$k]['content']=$login_userinfo."提现";
	                $mdrecord[$k]['type']="expend";
	            }elseif($v['action']=="changes"){//消耗钻石
	                $mdrecord[$k]['content']="钻石兑换M豆";
	                $mdrecord[$k]['type']="expend";
	            }elseif($v['action']=='get_gift'){//增加钻石
	                $mdrecord[$k]['content']="收到礼物";
	                $mdrecord[$k]['type']="incre";
	            }
	        }
	        /*elseif($v['action']=="get_gift"){//收到礼物增加钻石 移除
	         unset($mdrecord[$k]);
	         }elseif($v['action']=="changes"){//减少钻石(钻石换M豆  减少钻石)
	         unset($mdrecord[$k]);
	         }elseif($v['action']=="cash"){//钻石提现 减少钻石
	         unset($mdrecord[$k]);
	         }*/
	        /*$mdrecords=array_values($mdrecord);
	        $money_record = $this->sortByFieldASC($mdrecords,'addtime');*/
            $money_record=array_values($mdrecord);
	        if($money_record==null){
	            $result=[
	                'money_record'=>[],
	                'pageInfo'=>$pageInfo,
	            ];
	        }else{
	            $result=[
	                'money_record'=>$money_record,
	                'pageInfo'=>$pageInfo,
	            ];
	        }
	        $this -> returnCode = 200;
	        $this -> returnMsg = "操作成功";
	        $this -> returnData=$result;
	    }catch (Exception $e){
	        $this -> returnCode = $e ->getCode();
	        $this -> returnMsg = $e ->getMessage();
	    }
	    $this->returnData();
	}
	//排序
	private function sortByFieldASC($arrUsers,$field) {
	    $sort = array(
	        'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
	        'field'     => $field,       //排序字段
	    );
	    $arrSort = array();
	    foreach($arrUsers AS $uniqid => $row){
	        foreach($row AS $key=>$value){
	            $arrSort[$key][$uniqid] = $value;
	        }
	    }
	    if($sort['direction']){
	        array_multisort($arrSort[$sort['field']], constant($sort['direction']), $arrUsers);
	    }
	    return $arrUsers;
	}
	/**
	 * 客户端请求,统一支付入口(除苹果支付).
	 *
	 * @param string $action 支付方法
	 * @param integer    $uid    充值用户id
	 * @param integer    $rmb    充值金额
	 * @param string $token  系统token
	 * @param string $to_uid  被充值用户id
     * @param string $type 1 vip充值
     * @param string $is_active 状态 1续费vip 2激活vip
	 */
	public function pay($action, $uid, $rmb, $token,$type,$is_active)
	{
        $channel_data = isset($_SERVER['HTTP_XY_CHANNEL']) ? $_SERVER['HTTP_XY_CHANNEL'] : null;        //渠道
        if(empty($channel_data)){
            $channel = 1;
        }else{
            $channel = $channel_data;
        }
//        var_dump($channel);die();
		//检查必要参数
	  try{
		if(!isset($action) || !isset($uid) || !isset($rmb) || !isset($token) || $type == 2 || $rmb < 1){
			E('参数不全',2000);
		}else{
			$action = strtolower($action);
			// 通过token获取该用户的信息
			$userid = RedisCache::getInstance()->get($token);
		//	var_dump($userid);die;
			// 检查充值用户的id与token中的uid是否
			if($userid !=$uid){
				E('用户不匹配',2001);
			}
			//根据方法中是否包含关键字，判断支付类型
			if (strstr($action,"weixin")) {
				$platform = 1;// 微信
			} elseif (strstr($action,"alipay")) {
				$platform = 0;// 支付宝
			} else {
			    E('不存在该支付action',2002);
			}
		  // 获取虚拟币的数量(实际充值+赠送).
			/*$where['uid']=$userid;
			$coin = self::$list[$rmb]?self::$list[$rmb]["diamond"]:0;	
			//var_dump($coin);die;
			//$coin = self::$list[$rmb]?self::$list[$rmb]["diamond"]+self::$list["$rmb"]["present"]:0;
			// 创建订单.
			$orderNo = $this->createOrder($uid,$rmb,$coin,$platform);*/
	//		var_dump($orderNo);die;
		//	var_dump($orderNo);die;
            if($type==2){      //type 为2表示vip购买
                //根据rmb查询对应的时间
                $vip_days = D("vip")->getOneByIdField($rmb);
                $orderNo = $this->createOrder($uid,$rmb,$coin=$vip_days,$platform,$type,$is_active,$channel);
            }else if($type==1){
                $where['uid']=$userid;
                $coin = self::$list[$rmb]?self::$list[$rmb]["diamond"]:0;
                if ($coin == 0) {
                	E('支付比例错误',2003);
                }
                //var_dump($coin);die;
                //$coin = self::$list[$rmb]?self::$list[$rmb]["diamond"]+self::$list["$rmb"]["present"]:0;
                // 创建订单.
                $orderNo = $this->createOrder($uid,$rmb,$coin,$platform,$type,$is_active,$channel);
                //		var_dump($orderNo);die;
            }
			if (!$orderNo) {
				Log::record("创建订单失败----uid=". $uid.'---'.$action.'---'.$rmb, "INFO" );
				E('创建订单失败',2003);
			}
			// 请求第三方.
			switch ($action) {
				case "alipay": // 支付宝支付.
				    $alipay=self::$AliPay->aliPay($orderNo, $rmb);
				    $this -> returnCode = 200;
				    $this -> returnMsg = "操作成功";
				    $this -> returnData=$alipay;
					break;
				case "weixin": // 微信app支付.
					$weixin=self::$WeiXin->appWeiXin($orderNo, $rmb);
					$this -> returnCode = 200;
					$this -> returnMsg = "操作成功";
					$this -> returnData=$weixin;
					break;
				case "binarycodeweixin": // 微信开放平台扫码支付.
					self::$WeiXin->binaryCodeWeiXin($orderNo, $rmb);
					break;
				default: // 不存在该支付方式.
					$this->responseError(L("_NO_METHOD_"));
					break;
			}
		}
	  }catch(Exception $e){
	    $this -> returnCode = $e ->getCode();
	    $this -> returnMsg = $e ->getMessage();
	      
	  }
	  $this -> returnData();
	}

	/**
	 * 苹果支付.
	 * @param integer $uid         支付用户id
	 * @param integer $rmb         金额
	 * @param string  $token       验证token
	 * @param string  $deaild      苹果订单号
	 * @param string  $certificate 支付凭证
	 * @param string $product_id   苹果商品号
	 *
	 * @return mixed
	 */
	private function applePay($token,$uid, $rmb,$deaild,$certificate,$product_id='')
	{
	    try{
	        // 检查支付凭证.
	        if (empty($certificate)) {
	           E("没有支付凭证",2000);
	        }
	        // 检查订单状态.
	        $orderStatus = $this->getOrderInfo(["status" => 1,"dealid" => $deaild]);	        
	        if (!empty($orderStatus)) {
	           E("订单已经被支付",2001);
	        }	        
	        // 通过token获取该用户的信息.
	        $userid = RedisCache::getInstance()->get($token);	        
	        // 检查充值用户的id与token中的uid是否.
	        if($userid !=$uid){
	           E("用户不匹配",2002);
	        }	        
	        // 获取虚拟币的数量(实际充值+赠送).
	        $coin = self::$list[$rmb]?self::$list[$rmb]["diamond"]:0;	
	        // 创建系统订单.
	        //$uid, $rmb, $coin, $platform=0,$type,$is_active,$title='', $content = '', $dealid = 0
	        $orderNo = $this->createOrder($uid,$rmb, $coin, 2,1,0,$product_id, '苹果支付', $deaild);
	        // 如果订单号不存在.
	        if (empty($orderNo)) {
	           E("创建订单失败",2003);
	        }
	        // 保存支付凭证.
	        $createCertificate = $this->createCertificate($orderNo, $certificate);
	        // 保存支付凭证失败.
	        if (!$createCertificate) {
	           E("保存支付凭证失败",2003);
	        }	        
	        
	        // 验证支付凭证.
	        $checkCertificateStatus = self::$ApplePay->applePay($certificate);
	        
	        if (!$checkCertificateStatus) {
	            E('校验失败，系统将自动补偿，请耐心等待...',2004);
	        }

	        // 更新订单,并且返回金币.
	        $result = $this->updateOrder($orderNo, $deaild, "苹果官方支付", $rmb);
	        	        	       
	        if($result){
	            $time=date('Y-m-d H:i:s',time());
	            //更新订单成功，修改支付凭证状态
	            D('appcertificate')->updateCertificateStatus($orderNo,$createCertificate);
	            // var_dump($result);die;
	            // 获取订单信息.
	            $where=array("status" => 1, "orderno" => $orderNo);
	            $orderInfo = D('chargedetail')->getorder($where);
	            //加入流水表(增加m豆)
	            $data=array(
	                'uid'=>$orderInfo['uid'],
	                'action'=>"charges",
	                'content'=>'增加M豆',
	                'addtime'=>time(),
	                'status'=>"苹果内购",
	                'bean'=>$orderInfo['coin'],
	            );
	            // var_dump($data);die;
	            $beandetail= D('beandetail')->addDatas($data);
	            $this -> returnCode = 200;
	            $this -> returnMsg = "操作成功";
	        }else{
	           E('哎呀，系统开小差了，系统稍微为您自动恢复...',2005);
	        }
	    }catch(Exception $e){
	        $this -> returnCode = $e ->getCode();
	        $this -> returnMsg = $e ->getMessage();
	        
	    }
	    $this -> returnData();


	}

/**
	 * 第三方支付回调验签成功以后调用的统一方法，更新订单，给用户加值，推荐用户提成，站长提成等操作
	 * 事物操作.
	 * 现有分成：推荐人分成,站长推荐收益分成.
	 *
	 * @param string $orderNo 系统订单号.
	 * @param string $dealid  第三方订单号.
	 * @param string $content 内容描述.
	 * @param float  $rmb     充值金额.
	 *
	 * @return boolean
	 */
	public  function updateOrder($orderNo, $dealid, $content, $rmb)
	{

	        
	        // 获取订单信息.
	        $where=array("status" => 0, "orderno" => $orderNo);
	        $orderInfo = D('chargedetail')->getorder($where);
	        if (empty($orderInfo)) {
	            return false;
	        }
	        // 开启事物.
	        $model = D('chargedetail');
	        $model->startTrans();
	        // 2.更新订单状态.
//	       $updatestatus=$model->updateOrderStatus(array('dealid'  => $dealid, 'status'  => 1, 'content' => $content, 'rmb'=> $rmb), array('status'  => 0, 'orderno' => $orderNo));
//           $updatestatus=$model->where(array("status"=>0,"orderno"=>$orderNo))->setField(array('dealid','status','content','rmb'),array($dealid,1,$content,$rmb));
            $dataes['status'] = 1;
            $dataes['dealid'] = $dealid;
            $dataes['content'] = $content;
            $updatestatus=$model->where(array("status"=>0,"orderno"=>$orderNo))->save($dataes);
            Log::record("订单状态". json_encode(json_encode($updatestatus)), "INFO" );
        // 3. 给用户加值.
            if($orderInfo['type'] == 1){
                $increcoin= D('member')->where(array("id"=>$orderInfo["uid"]))->setInc('totalcoin',$orderInfo["coin"]);
                //等级修改
                $totalcoin = floor(MemberService::getInstance()->getOneByIdField($orderInfo['uid'],"totalcoin"));       //获取此用户的充值
//                $chargecoin = M('member')->where(array("id"=>$orderInfo['uid']))->setInc('chargecoin',$orderInfo["coin"]);
//                $deng_chargecoin = floor(MemberService::getInstance()->getOneByIdField($orderInfo['uid'],"chargecoin"));         //当前充值的虚拟币
                $lv_dengji = lv_dengji($totalcoin);     //获取等级
                $result_dengji = array('lv_dengji'=>$lv_dengji);        //修改的字段值
                D('member')->updateDate($orderInfo['uid'],$result_dengji);        //修改等级
                $userKey = "userinfo_";     //redis缓存更新
                RedisCache::getInstance()->getRedis()->hMset($userKey.$orderInfo['uid'], array('lv_dengji'=>$lv_dengji,'totalcoin'=>$totalcoin));        //更改缓存等级与总虚拟币
//                $lv_dengji = lv_dengji($totalcoin);     //获取等级
//                $data_dengji = array('lv_dengji'=>$lv_dengji);        //修改的字段值
//                D('member')->updateDate($orderInfo['uid'],$data_dengji);        //修改等级

             /*   if ($updatestatus && $increcoin ) {
                    $model->commit();
                    return true;
                } else {
                    $model->rollback();
                    return false;
                }*/
            }else if($orderInfo['type'] == 2){
                $increcoin = VipController::chargeVipes($rmb,$orderInfo['uid'],$orderInfo['coin'],$orderInfo['is_active']);
            }
            if ($updatestatus && $increcoin) {
                $model->commit();
                return true;
            } else {
                $model->rollback();
                return false;
            }
	    /*   $increcoin= D('member')->where(array("id"=>$orderInfo["uid"]))->setInc('totalcoin',$orderInfo["coin"]);
	        if ($updatestatus && $increcoin ) {
	                $model->commit();
	                return true;
	            } else {
	                $model->rollback();
	                return false;
	           }*/
	}

	/**
	 * 创建订单的唯一方法.
	 *
	 * @param integer     $uid      充值用户id
	 * @param integer     $touid    被充值用户id
	 * @param integer     $rmb      金额，单位元
	 * @param integer     $coin     虚拟币
	 * @param integer     $platform 平台类型,0支付宝，1微信，2苹果
     * @param integer     $type     1充值 2vip购买
     * @param integer     $is_active    //状态 1续费vip 2激活vip 0充值
	 * @param string      $title    标题描述
	 * @param string      $content  内容描述
	 * @param integer     $dealid   第三方订单号
	 * @param string      $channel   渠道
	 * @return string
     * $orderNo = $this->createOrder($uid,$rmb,$coin,$platform,$type,$is_active,$channel);
	 */
	private function createOrder($uid, $rmb, $coin, $platform=0,$type,$is_active,$channel,$title='', $content = '', $dealid = 0)
	{
		//生成订单号
		$orderNo = $this->createOrderNo($uid);
		//var_dump($orderNo);die;
		$data = [
			'uid'      => $uid,
			'rmb'      => $rmb,
			'coin'     => $coin,
			'content'  => $content,
			'status'   => 0,
			'orderno'  => $orderNo,
			'addtime'  => date('Y-m-d H:i:s',time()),
			'dealid'   => $dealid,
			'platform' => $platform,
			'title'    => $title,
            "type"     => $type,        //1充值 2vip购买
            "is_active" =>$is_active,         //is_active 状态 1续费vip 2激活vip 0充值
            "channel" => $channel,
		];
//        var_dump($data);die();
		// 创建订单.
		//var_dump($data);die;
		$createOrderSuccessOrFail =D('chargedetail')->addData($data);
     //   var_dump($createOrderSuccessOrFail);die;
		// 创建订单成功，返回订单号.
		if ($createOrderSuccessOrFail) {
			return $orderNo;
		} else {
			return false;
		}

	}

	/**
	 * 生成唯一的订单号
	 */
	private function createOrderNo($uid)
	{
		// 生成订单号.
		$orderNo = $uid.time().rand(10000,99999);

		// 检查订单是否存在.
		$orderNoExist = $this->getOrderInfo(["orderno" => $orderNo]);
		// 如果生成失败，再次调用该方法.
		if ($orderNoExist) {
			$orderNo = $this->createOrderNo($uid);
		}
           // var_dump($orderNo);die;
		return $orderNo;
	}

	/**
	 * 查看系统订单信息.
	 *
	 * @param $where array 系统订单的查询条件.
	 */
	private function getOrderInfo($where)
	{//var_dump(123);die;
		$orderInfo =D('chargedetail')->getorder($where);
		//var_dump($orderInfo);die;
		return $orderInfo;
	}

	/**
	 * 写入appstore支付凭证
	 *
	 * @param int $order_id
	 * @param string $certificate app发过来的支付凭证
	 *
	 * @return bool
	 */
	private function createCertificate($orderNo, $certificate)
	{
		// 通过订单号$orderNo获取主键id.
		$orderInfo = $this->getOrderInfo(["orderno" => $orderNo]);
		// 待添加数据.
		$data = [
			'order_id'    => $orderInfo["id"],
			'certificate' => $certificate,
			'checked'     => 0
		];
		$data=D('appcertificate')->addData($data);
		return $data;
	}

	/**
	 * @param string $orderNo     订单号.
	 * @param
	 */
	private function updateCertificateStatus($orderNo, $certificate)
	{
		// 通过订单号$orderNo获取主键id.
		$orderInfo = $this->getOrderInfo(["orderno" => $orderNo]);
		$where["orderno"]     = $orderInfo["id"];
		$where["certificate"]  = $certificate;
		$where["checked"]     = 0;
		$param["checked"]     = 1;
		return D('appcertificate')->updateCertificate($where,$param);
	}

	/**
	 * 记录一条充值数据到钱币流水表中.
	 *
	 * @param string $uid 待添加的数据.
	 * @param string $cash 待添加的数据.
	 * @param string $type 待添加的数据.
	 * @param string $channelid 待添加的数据.
	 * @param string $checkname 待添加的数据.
	 */
    public function belowline($uid,$cash,$type,$channelid,$checkname){
        if($cash<100){
            $this->responseError("提现金额不能小于100元");
        }
        $checkchsh = '/^[0-9][0-9]*$/';
        if(!preg_match($checkchsh,$cash)){
            $this->responseError("提现金额必须为整数");
        }
        //是否实名认证  0:未审核,1:审核通过,2:审核不通过 3没有实名认证
        $is_renzheng = M('name_authentication')->where(array("user_id"=>$uid))->find();
        if($is_renzheng['status'] == 3 || $is_renzheng['status'] == 2){
            $this->responseError("请实名认证");
        /*}else{
            $this->responseError("请实名认证");*/
        }
		$host = \MClient\Text::inst('api')->setClass("User")->getByWhere("id,nickname,agentuid,commission,beanbalance,earnbean", array("id" => $uid));
        $probability = \MClient\Text::inst('api')->setClass('SiteConfig')->getSiteConfig('cash_proportion','id = 1');
        $probability = $probability['cash_proportion'];
        //判断此主播是否关联经纪人
        if($host["agentuid"] != "0"){
            if($type == ''){
                $type = 9;//9：线下提交。因为需求要求，暂时固定类型为9
            }
            $bean = sprintf('%.2f',$cash *$probability );
            $pumping = ($host["commission"] / $probability) * $cash;
            $add = array(
                    "uid" => $uid,//主播ID
                    "hostname" => $host["nickname"],//主播昵称
                    "cash" => $cash,//金额
                    "coin" => $bean,//coin
                    "checkname" => $checkname,//姓名
                    "channelid" => $channelid,//账户
                    "time" => time(),//提现时间
                    "type" => $type,
                    "status" => "待审核",//提现状态
                    "agentid" => $host["agentuid"],//经纪人ID
                    "agentredio" => $host["commission"],//提成率
                    "phone" => $_REQUEST['phone'],
                    "pumping" => $pumping,//提成金额
                   
            );
            //$user_res = M('member')->where('id ='.$uid)->setDec('beanbalance',$bean);
//            $updata['beanbalance'] = $host['beanbalance'] - $bean;
            $updata['earnbean'] = $host['earnbean'] - $bean;
            $updatawhere['id'] = $uid;
            $user_res = \MClient\Text::inst('api')->setClass("User")->update($updata,$updatawhere);
            if($user_res){
                //$res = M('earncash')->add($add);
                $res = \MClient\Text::inst('api')->setClass("Earncash")->create($add);
                $res ? $this->responseSuccess('提交成功') :$this->responseError('提交异常,稍后重试(02)');
            }else{
                $this->responseError('提交异常,稍后重试(03)');
            }
        }else{
            $type = '9'.$type;
            $types = array(91,92,93);
            //$userinfo = M('member')->where('id = '.$uid)->find();
            if(empty($host)){
                $this->responseError('提交异常,稍后重试(01)');
            }
            if(!is_numeric($cash)){
                $this->responseError('金额异常,稍后重试');
            }
            if(!in_array($type,$types)){
                $this->responseError('提交异常,稍后重试(02)');
            }
            //$probability = M('siteconfig')->where('id=1')->getField('cash_proportion');
            $bean = sprintf('%.2f',$cash*$probability);
//            if($bean > $host['beanbalance']){
            if($bean > $host['earnbean']){
                $this->responseError('提交异常,提交金额大于余额');
            }
            $data["cash"] = $cash;
            $data["coin"] = $bean;
            $data["uid"] = $uid;
            $data["status"] = '待审核';
            $data["time"] = time();
            $data["type"] = (int)$type;
            $data['channelid'] = $channelid;
            $data['checkname'] = $checkname;
            $data["phone"] = $_REQUEST['phone'];
            $data['apliuserid']=$_REQUEST['apliuserid'];
            //$user_res = M('member')->where('id ='.$uid)->setDec('beanbalance',$bean);
//            $updata['beanbalance'] = $host['beanbalance'] - $bean;
            $updata['earnbean'] = $host['earnbean'] - $bean;
            $updatawhere['id'] = $uid;
            $user_res = \MClient\Text::inst('api')->setClass("User")->update($updata,$updatawhere);
            if($user_res){
                //$res = M('earncash')->add($data);
                $res = \MClient\Text::inst('api')->setClass("Earncash")->create($data);
                $res ? $this->responseSuccess('提交成功') :$this->responseError('提交异常,稍后重试(02)');
            }else{
                $this->responseError('提交异常,稍后重试(03)');
            }
        }
    }
}
 
