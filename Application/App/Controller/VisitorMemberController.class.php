<?php
namespace Api\Controller;

use Api\Service\VisitorMemberService;
use Api\Service\CoindetailService;
use Api\Service\MemberService;
use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Think\Log;

class VisitorMemberController extends BaseController {

    /**
     * 最近访客历史记录
     * @param $token token值
     * @param $signature 签名MD5(小写） token+page
     */
    public function getList($token,$page,$signature){
        //获取数据
        $data = [
            "token" => I('post.token'),
            "page" => I('post.page'),
            "signature" => I('post.signature'),
        ];
        $uid = RedisCache::getInstance()->get($data['token']);
//        var_dump($uid);die();
        try{
            //校验数据
            ParamCheck::checkInt("page",$data['page'],1);
            /*if($data['signature'] !== Md5(strtolower($data['token'].$data['page']))){
                E("验签失败",2000);
            }*/
            // 每页条数
            $size = 20;
            $pageNum = empty($size)?20:$size;
            $page = empty($data['page'])?1:$data['page'];
            $where['touid'] = $uid;
            $count =VisitorMemberService::getInstance()->countes($where);
            //修改查看用户的状态
            VisitorMemberService::getInstance()->updateStatus($where);
//            var_dump($count);die();
            // 总页数.
            $totalPage = ceil($count/$pageNum);
            // 页数信息.
            $pageInfo = array("page" => $page, "pageNum"=>$pageNum, "totalPage" => $totalPage);
            $limit = ($page-1) * $size . "," . $size;
            //数据操作formatTimes D("member")->merge_exp($user_id);     //统计当前用户的经验值
            $list = VisitorMemberService::getInstance()->getList($uid,$limit);
            foreach($list as $key=>$value){
                $list[$key]['avatar'] = getavatar($value['avatar']);        //头像
                $list[$key]['ctime'] = formatTimes($value['ctime']);        //访问时间
                $list[$key]['lv_dengji'] = lv_dengji(floor($value['totalcoin']));  //等级
                $total_expvalue = D("member")->merge_exp($value['user_id']);
                $list[$key]['vip_dengji'] = vip_grade($total_expvalue['total_expvalue']);  //vip等级
                //根据当前用户uid,去查询对应访问user_id的消费总和,(uid向此用户user_id消费总和)
                $duke_coin = CoindetailService::getInstance()->getMembercoin($uid,$value['user_id']); //用户统计数据操作
                $list[$key]['duke_grade'] = duke_grade($duke_coin[0]['coin']);  //爵位等级
                unset($list[$key]['totalcoin']);
            }
            // var_dump($data);die();
            if(is_null($list)){
                $list = [];
            }
            $result = [
                "history_list" => $list,
                "pageInfo" => $pageInfo,
            ];
            $this -> returnCode = 200;
            $this -> returnData = $result;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

    }

}


?>