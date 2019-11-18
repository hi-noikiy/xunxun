<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Think\Controller;
use Think\Exception;
use Common\Util\ParamCheck;
use Think\Log;
use Common\Util\RedisCache;

class UserinfoController extends BbaseController {
    /**
     * 用户列表缓存数据
     */
    public function userList(){
        $userKey = "userinfo_";
      //   $list = M("member")->field('id,avatar')->select();
      //   $log ='/tmp/userinfo_'.date('Ymd',time()).'.log';  
      // foreach($list as $key=>$value){
      //   RedisCache::getInstance()->getRedis()->hset($userKey.$value['id'],'avatar',$value['avatar']);
      //   $a = "hset ".$userKey.$value['id']." avatar ".$value['avatar'];
      //   file_put_contents($log,$a.PHP_EOL,FILE_APPEND);   
      // }
      //   echo 'done';
      //   exit;

        
        // $log = '/tmp/redis_aa';
        // $a = "hset ".$userKey.$value['id']." avatar ".$value['avatar'];
        // file_put_contents($log,json_encode($response_voice).PHP_EOL,FILE_APPEND); 
        //拼接数据
        exit;
        try{
            for ($i=0; $i < 50000; $i=$i+5000) {
                //获取所有用户的列表数据
                $t = $i.',5000';
                $list = M("member")->limit($t)->select();
                if (empty($list)) {
                    return;
                }
                foreach($list as $key=>$value){
                    //登录成功后将用户的基本信息存储在redis的hash中
                    RedisCache::getInstance()->getRedis()->hmset($userKey.$value['id'],$value);
                }
            }
            //查询成功
            $this -> returnCode = 200;
        }catch(\Exception $e){
            $this -> returnCode = $e ->getCode();
            $this -> returnMsg = $e ->getMessage();
        }
        $this->returnData();

    }


}


?>