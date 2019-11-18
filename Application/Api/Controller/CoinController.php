<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Think\Controller;
use Think\Exception;
use Think\Log;
use Common\Util\RedisCache;

class CoinController extends BbaseController {
    /**
     * 加豆
     */
    public function addcoin(){
        $userKey = "userinfo_";
        $list = M("member")->field('id,avatar')->select();
        $log ='/tmp/redisaa.log';  
      foreach($list as $key=>$value){
        RedisCache::getInstance()->getRedis()->hset($userKey.$value['id'],'avatar',$value['avatar']);
        $a = "hset ".$userKey.$value['id']." avatar ".$value['avatar'];
        file_put_contents($log,$a.PHP_EOL,FILE_APPEND);   
      }
        echo 'done';
        exit;

        
        $log = '/tmp/redis_aa';
        $a = "hset ".$userKey.$value['id']." avatar ".$value['avatar'];
        file_put_contents($log,json_encode($response_voice).PHP_EOL,FILE_APPEND); 
        //拼接数据
        
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
                    RedisCache::getInstance()->hmset($userKey.$value['id'],$value);
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