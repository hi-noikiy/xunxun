<?php

namespace Api\Controller;
use Think\Controller;
use Common\Util\RedisCache;


class FeedbackController extends BaseController {

    /**意见反馈接口
     * @param $token    token值
     * @param $signature    签名(md5(strtolower(token)))
     */
	public function setFeedback($token,$content,$signature=null){
        $data = [
            "token" => I('post.token'),
            "content" => I('post.content'),
            "signature" => I('post.signature'),
        ];

		try{
            $user_id = RedisCache::getInstance()->get($data['token']);
            /*if($data['signature']!== md5(strtolower($data['token']))){
                E("验签失败",2000);
            }*/
            $pattern = "/^[\x{4e00}-\x{9fa5}]{1,1000}+$/u";
            if(!preg_match($pattern, $data['content'])){
                E("请输入有效有文字",2000);
            }
			//数据操作
            $feedback_data = [
                "user_id" => $user_id,
                "content" => $data['content'],
                "create_time" => time(),
            ];
//            var_dump($feedback_data);die();
			D('Feedback') -> addData($feedback_data);
			//反馈成功
			$this -> returnCode = 200;
		}catch(\Exception $e){
			$this -> returnCode = $e ->getCode();
			$this -> returnMsg = $e ->getMessage();
		}
		$this->returnData();

	}
}


?>