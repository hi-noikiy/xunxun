<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\Easemob;
use Think\Exception;
use Think\Log;
 class ApproviceController extends BaseController{
        //初始化协议列表
        public function index(){
            $this->display();
        }

        /**语言认证
        * @param $token   token值
        * @param $signature   签名(md5(strtolower(token))
        */
        public function getContent($token,$signature){
            $data = [
                "token" => I('post.token'),
                "signature" => I('post.signature'),
            ];
            try{
                //校验签名
                if($data['signature'] !== Md5(strtolower($data['token']))){
                    E("验签失败",2000);
                }
                $content_list =D('siteconfig')->getField('renzheng_content');
                $result = [
                    "content_list" => $content_list,
                ];
                $this -> returnCode = 200;
                $this -> returnData = $result;
            }catch(\Exception $e){
                $this -> returnCode = $e ->getCode();
                $this -> returnMsg = $e ->getMessage();
            }

            $this -> returnData();
        }

        /**实名认证接口
        * @param $token   token值
        * @param $name    真实姓名
        * @param $card    身份证
        * @param $card_beforepic  身份证正面图片
        * @param $card_afterpic   身份证反面图片
        * @param $card_authorpic  手持身份证图片
        * @param $signature 验签数据 md5(strtolower(token))
        */
       public function createNameCard($token,$name,$card,$signature=null){
           //获取数据
           $data =[
               "token" => I('post.token'),
               "name" => I('post.name'),
               "card" => I('post.card'),
               "signature" => I('post.signature'),
           ];
           try{
               //校验数据
              /* $checkname = '/^[\x{4e00}-\x{9fa5}]{2,10}$|^[a-zA-Z\s]*[a-zA-Z\s]{2,20}$/isu';  //姓名
                if(!preg_match($checkname,$name)){
                   E("姓名不正确",2002);
                }*/
               $checkcard = '/^\d{15}|\d{18}$/';       //身份证
               if(!preg_match($checkcard,$data['card'])){
                   E("身份证号码不正确",2003);
               }
               $user_id = RedisCache::getInstance()->get($data['token']);
               //该用户是否提交认证过
               $is_approve = D('approve')->approveinfo($user_id);
               if($is_approve['status'] != 2 && $is_approve['user_id'] !=null){
//                   E("您的认证正在审核在中,请耐心等候.",2000);
               }
               $approve = [];
               $approve = [
                   "user_id" => $user_id,
                   "uptime" => time(),
                   "status" => 0,
                   "card" => $data['card'],
                   "nickname" => $data['name'],
               ];
               $files=$_FILES;
//               $count=count($files);
               foreach($files as $k=>$v){
                   $upload = new \Think\Upload();// 实例化上传类
//                   $upload->maxSize = 1024*1024*10 ;// 设置附件上传大小20M
                   $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
//                   $upload->exts  =  array('cd', 'ogg', 'mp3', 'asf','wma','wmv','mp3pro','rm','real','ape','module','midi','vqf','jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
                   $upload->rootPath  = './Public/'; // 设置附件上传根目录
                   $upload->savePath = '/Uploads/approvice/'; // 设置附件上传目录
                   $upload->autoSub   = true;
               }
               $info   =   $upload->upload();
//               var_dump($upload->getError());die;
               if(!$info) {// 上传错误提示错误信息
                  /* $result = array(
                       'status' => 0,
                       'error' => $upload->getError()
                   );*/
                   E("认证用户数据有误",2000);
//                   die();
               }else{
                   foreach ($info as $key => $file) {
                       $approve[$key] = '/Public'.$file['savepath'] . $file['savename'];
                   }

               }
               //加入数据库操作
               $result_approve = D("approve")->addData($approve);
               //根据用户去修改个人中心里面的状态值
               $update = [
                   "attestation" => 3,      //改变用户认证状态 0未提交 1认证 2未通过 3审核中
               ];
               D('member')->updateDate($user_id,$update);
               $this -> returnCode = 200;
           }catch(Exception $e){
               $this -> returnCode = $e ->getCode();
               $this -> returnMsg = $e ->getMessage();
               
           }
           $this -> returnData();

       }

     /**用户语言认证接口功能
      * @param $token token值
      * @param $avatar   avatar上传图片的详情
      * @param $signature   签名(md5(strtolower(token))
      */
     public function createLang($token,$signature){
         $data['token'] = I('post.token');
         $data['signature'] = I('post.signature');
         $user_id = RedisCache::getInstance()->get($data['token']);
         //判断当前用户是否提交过认证
         $is_approve = D("lang_approve")->approveinfo($user_id);
//         var_dump($is_approve);die();
         /*if($is_approve['status'] != 2 && $is_approve['user_id'] != null){
             E("您的认证正在审核在中,请耐心等候",2000);
         }*/
         //校验签名
         /*if($data['signature']!== md5(strtolower($data['token']))){
             E("验签失败",2000);
         }*/
         Log::record("talk_url". json_encode(json_encode($_FILES["talk_url"])), "INFO" );
         //获取图片
         if($_FILES["talk_url"]["error"] != 0){
             E("上传语言有误",2000);
//             $this -> error("上传语言有误");
             die();
         }
         // 处理图片
         $upload = new \Think\Upload();// 实例化上传类
         $upload->maxSize = 1024*1024*10 ;// 设置附件上传大小 5M
//         $upload -> exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
//         $upload->exts = array('cd', 'ogg', 'mp3', 'asf','wma','wmv','mp3pro','rm','real','ape','module','midi','vqf','mpeg3','audio');// 设置附件上传类型
         $upload->rootPath = './Public';
         $upload->savePath = '/Uploads/language_approvice/'; // 设置附件上传目录
         // 上传文件
         $info = $upload->upload();
         if(!$info) {// 上传错误提示错误信息
             $this->error($upload->getError());
             E("认证用户数据有误",2000);
             die();
         }else{// 上传成功
             $_POST["talk_url"] = '/Public'.$info["talk_url"]["savepath"].$info["talk_url"]["savename"];
         }
         try{
             $dataes = [
                 "user_id" => $user_id,
                 "talk_url" => $_POST["talk_url"],
                 "uptime" => time(),
                 "status" => 0,
             ];
             //数据操作
             $result_approve = D("lang_approve")->add($dataes);
             //根据用户去修改个人中心里面的状态值
             $update = [
                 "lang_approve" => 3,      //改变语言状态 0未提交 1认证 2未通过 3审核中
             ];
             D('member')->updateDate($user_id,$update);
             if($result_approve){
                 $this -> returnCode = 200;
             }else{
                 E("认证用户数据有误",2000);
             }
         }catch (Exception $e){
             $this -> returnCode = $e ->getCode();
             $this -> returnMsg = $e ->getMessage();
         }

         $this->returnData();
     }

}