<?php
namespace Api\Controller;

use Api\Service\MemberService;
use Think\Controller;
use Common\Util\ParamCheck;
use Common\Util\RedisCache;
use Common\Util\Easemob;
use Common\Util\UploadYun;
use Think\Exception;
use Think\Log;
use OSS\Core\OssException;
use OSS\OssClient;

 class ApproviceController extends BaseController{
        //初始化协议列表
        public function index(){
            $this->display();
        }

     /**查询实人认证提交接口
      * @param $token   token值
      * @param $signature   签名(md5(strtolower(token))
      */
        public function getapprove($token,$signature=null){
            //获取值
            $data = [
                "token" => I('post.token'),
                "signature" => I('post.signature'),
            ];
            try{
                $user_id = RedisCache::getInstance()->get($data['token']);     //用户id
                //校验签名
//                if($data['signature'] !== Md5(strtolower($data['token']))){
//                    E("验签失败",2000);
//                }
                $face_detect_result  = M('face_detect')->where(array("user_id"=>$user_id))->order('id desc')->find();
                if($face_detect_result){
                    if($face_detect_result['status_code'] == 1){       //该用户已认证成功操作
                        E("该用户已成功认证",2001);
                    }else{  //查询该用户人脸识别接口
                        //调取阿里云人脸识别
                        $UploadYun = new UploadYun();
                        $TicketId_result = $UploadYun->getStatusTic($face_detect_result['ticket_id']);
                        if($TicketId_result['STATUSCODE'] == -1){      //E("该用户没有认证平台",2000);
                            E("该用户没有认证",2001);
                        }else if($TicketId_result['STATUSCODE'] == 0){      //该用户认证中
                            E("该用户认证中",2002);
                        }else if($TicketId_result['STATUSCODE'] == 1){     //E("该用户认证通过",2000);
                            E("该用户认证通过",2003);
                        }else if($TicketId_result['STATUSCODE'] == 2){         //E("该用户认证未通过",2000);
                            E("该用户认证未通过",2004);
                        }
                    }
                }else{
                    E("该用户未提交认证",2005);
                }
            }catch(\Exception $e){
                $this -> returnCode = $e ->getCode();
                $this -> returnMsg = $e ->getMessage();
            }
            $this -> returnData();
        }

     /**获取方便资料接口
      * @param $token
      * @param $signature
      */
     public function createapprove($token,$signature){
         //获取值
         $data = [
             "token" => I('post.token'),
             "signature" => I('post.signature'),
         ];
//         $url = 'https://ss0.bdstatic.com/70cFvHSh_Q1YnxGkpoWK1HF6hhy/it/u=3659112371,3007236965&fm=11&gp=0.jpg';
////         $url = 'http://verify-img.cn-shanghai.img.aliyun-inc.com/22c37c5dfb36427eb6bbc0a90164ecfdOSS.JPG?Expires=1563532680&OSSAccessKeyId=IJ95qE4nJQY6t6Lk&Signature=w7AWKRDNexfRI7%2B39eZh9K0Z5f0%3D';
////         $result = $this->GrabImage($url,$user_id."pic.png");
////         var_dump($result);die();
//         $user_id = "100064";
//         $frontpic['save_path'] = $this->GrabImage($url,$user_id."pic.png");
         try{
             $user_id = RedisCache::getInstance()->get($data['token']);     //用户id
             //校验签名
//             if($data['signature'] !== Md5(strtolower($data['token']))){
//                 E("验签失败",2000);
//             }
             $face_detect_result  = M('face_detect')->where(array("user_id"=>$user_id))->order('id desc')->find();
             if($face_detect_result){
                 if($face_detect_result['status_code'] == 1){       //该用户已认证成功操作
                     E("该用户已成功认证",2001);
                 }else{  //查询该用户人脸识别接口
                     M()->startTrans();
                     $faceresult =  M('face_detect')->where(array("user_id"=>$user_id,"ticket_id"=>$face_detect_result['ticket_id']))->save(array("status_code"=>1));
                     //调取阿里云人脸识别
                     $UploadYun = new UploadYun();
                     $getDetail = $UploadYun->getDetail($face_detect_result['ticket_id']);
                     //将第三方的图片下载到服务器本地中
                     //身份证正面图下载
                     $frontpic = $getDetail['IDCARDFRONTPIC'];
                     $upload = "Public/Uploads/approvice/";
//                     echo $upload.$this->GrabImage($frontpic,$user_id."_fronpic.png");die();
                     $frontpic = $upload.$this->GrabImage($frontpic,$user_id."_fronpic.png");
                     //身份证背面图下载
                     $backpic = $getDetail['IDCARDBACKPIC'];
                     $backpic = $upload.$this->GrabImage($backpic,$user_id."_backpic.png");
                     //人像下面图下载
                     $facepic = $getDetail['FACEPIC'];
                     $facepic = $upload.$this->GrabImage($facepic,$user_id."_facepic.png");
                     //将获取资料,并将资料保存在 zb_approve中
                     $approve = [
                         "user_id" => $user_id,
                         "card" => $getDetail['IDENTIFICATIONNUMBER'],  //身份证号码
                         "nickname" => $getDetail['NAME'],  //用户名称
                         "card_beforepic" => $frontpic,      //正面图
                         "card_afterpic" => $backpic,        //背面图
                         "card_authorpic" => $facepic,             //认证过程中拍摄的人像正面照图片HTTP地址
                         "status" => 1,         //审核通过
                     ];
                     M('approve')->save();
                     $approve = M('approve')->add($approve);
//                     echo M('approve')->getLastSql();
                     //修改用户表的认证状态
                     $member = M('member')->where(array("id"=>$user_id))->save(array('attestation'=>1));
//                     echo M('member')->getLastSql();
                     if($faceresult && $approve && $member){
                         M()->commit();
                         E("该用户认证成功",2007);
                     }else{
                         M()->rollback();
                         E("该用户认证失败",2006);
                     }

                 }
             }else{
                 E("该用户未提交认证",2005);
             }
         }catch(\Exception $e){
             $this -> returnCode = $e ->getCode();
             $this -> returnMsg = $e ->getMessage();
         }

         $this -> returnData();
     }

     /**
      * 从网上下载图片保存到服务器
      * @param $path 图片网址
      * @param $image_name 保存到服务器的路径 './public/upload/users_avatar/'.time()
      */
     private function saveImage($path, $image_name)
     {
         $ch = curl_init($path);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
         $img = curl_exec($ch);
         curl_close($ch);
//$image_name就是要保存到什么路径,默认只写文件名的话保存到根目录
         $fp = fopen($image_name, 'w');//保存的文件名称用的是链接里面的名称
         fwrite($fp, $img);
         fclose($fp);
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

     /**认证token接口第三方人脸识别
      * @param $token   用户token
      * @param $signature       签名md5(strtolower(token))
      */
     public function getVerifyToken($token,$signature=null){
         //获取值
         $data = [
             "token" => I('post.token'),
             "signature" => I('post.signature'),
         ];
         try{
             //校验签名
//             if($data['signature'] !== Md5(strtolower($data['token']))){
//                 E("验签失败",2000);
//             }
             $user_id = RedisCache::getInstance()->get($data['token']);     //用户id
             //调取阿里云人脸识别
             $UploadYun = new UploadYun();
             //查询当前用户是否成功认证(认证成功)
             $face_detect_result  = M('face_detect')->where(array("user_id"=>$user_id))->order('id desc')->find();
             if($face_detect_result['status_code'] == 1){       //该用户已认证成功操作
                 E("该用户已成功认证",2001);
             }
//             $approve_result = M("approve")->where(array("user_id"=>$user_id))->find();
//             if($approve_result){
//                 E("该用户已成功认证",2001);
//             }else{
//                 $result = $UploadYun->getStsInfo($user_id);
//                 $VerifyToken = [
//                     "Token" => $result['TOKEN'],
//                     "DurationSeconds" => $result['DURATIONSECONDS'],
//                 ];
//                 $this -> returnCode = 200;
//                 $this -> returnData = $VerifyToken;
//             }
             //查询当前用户是否成功认证(该用户没有认证或者该用户认证会话过期操作)
             if(empty($face_detect_result) || (time() - $face_detect_result['creat_time'])>1800){
                 $result = $UploadYun->getStsInfo($user_id);
                 $VerifyToken = [
                     "Token" => $result['TOKEN'],
                     "DurationSeconds" => $result['DURATIONSECONDS'],
                 ];
                 $this -> returnCode = 200;
                 $this -> returnData = $VerifyToken;
             }else{
                 $TicketId_result = $UploadYun->getStatusTic($face_detect_result['ticket_id']);
//             $TicketId_result['STATUSCODE'] = 1;
                 if($TicketId_result['STATUSCODE'] == -1){      //E("该用户没有认证平台",2000);
                     $face_detect_result  = M('face_detect')->where(array("user_id"=>$user_id))->order('id desc')->find();
                     M('face_detect')->where(array("user_id"=>$user_id,"ticket_id"=>$face_detect_result['ticket_id']))->save(array("status_code"=>2));
                     if($face_detect_result){
                         $VerifyToken = [
                             "Token" => $face_detect_result['verify_token'],
                         ];
                         file_put_contents("/tmp/11.log","aliyun--".date("Y-m-d H:i:s",time()).":".json_encode($VerifyToken)."".PHP_EOL,FILE_APPEND);
                         $this -> returnCode = 200;
                         $this -> returnData = $VerifyToken;
                     }else{
                         if(empty($face_detect_result) || (time() - $face_detect_result['creat_time'])>1800){
                             $result = $UploadYun->getStsInfo($user_id);
                             $VerifyToken = [
                                 "Token" => $result['TOKEN'],
                                 "DurationSeconds" => $result['DURATIONSECONDS'],
                             ];
                             file_put_contents("/tmp/22.log","aliyun--".date("Y-m-d H:i:s",time()).":".json_encode($VerifyToken)."".PHP_EOL,FILE_APPEND);
                         }else{
                             $VerifyToken = [
                                 "Token" => $face_detect_result['verify_token'],
                             ];
                         }
                         $this -> returnCode = 200;
                         $this -> returnData = $VerifyToken;
//                         $result = $UploadYun->getStsInfo($user_id);
//                         $VerifyToken = [
//                             "Token" => $result['TOKEN'],
//                             "DurationSeconds" => $result['DURATIONSECONDS'],
//                         ];
//                         $this -> returnCode = 200;
//                         $this -> returnData = $VerifyToken;
                     }
                 }else if($TicketId_result['STATUSCODE'] == 0){
                     M('face_detect')->where(array("user_id"=>$user_id,"ticket_id"=>$face_detect_result['ticket_id']))->save(array("status_code"=>0));
                     E("该用户认证中",2002);
                 }else if($TicketId_result['STATUSCODE'] == 1){     //E("该用户认证通过",2000);
//                  //开启事务
                     M()->startTrans();
                     $faceresult =  M('face_detect')->where(array("user_id"=>$user_id,"ticket_id"=>$face_detect_result['ticket_id']))->save(array("status_code"=>1));
                     //调取阿里云人脸识别
                     $UploadYun = new UploadYun();
                     $getDetail = $UploadYun->getDetail($face_detect_result['ticket_id']);
                     //将第三方的图片下载到服务器本地中
                     $upload = "Public/Uploads/approvice/";
                     //身份证正面图下载
                     $frontpic = $getDetail['IDCARDFRONTPIC'];
                     $frontpic = $upload.$this->GrabImage($frontpic,$user_id."_fronpic.png");
                     //身份证背面图下载
                     $backpic = $getDetail['IDCARDBACKPIC'];
                     $backpic = $upload.$this->GrabImage($backpic,$user_id."_backpic.png");
                     //人像下面图下载
                     $facepic = $getDetail['FACEPIC'];
                     $facepic = $upload.$this->GrabImage($facepic,$user_id."_facepic.png");
                     //将获取资料,并将资料保存在 zb_approve中
                     $approve = [
                         "user_id" => $user_id,
                         "card" => $getDetail['IDENTIFICATIONNUMBER'],  //身份证号码
                         "nickname" => $getDetail['NAME'],  //用户名称
                         "card_beforepic" => $frontpic,      //正面图
                         "card_afterpic" => $backpic,        //背面图
                         "card_authorpic" => $facepic,             //认证过程中拍摄的人像正面照图片HTTP地址
                         "status" => 1,         //审核通过
                     ];
                     M('approve')->save();
                     $approve = M('approve')->add($approve);
                     //修改用户表的认证状态
                     $member = M('member')->where(array("id"=>$user_id))->save(array('attestation'=>1));
                     if($faceresult && $approve && $member){
                         M()->commit();
                         E("该用户认证成功",2007);
                     }else{
                         M()->rollback();
                         E("该用户认证失败",2006);
                     }
                 }else if($TicketId_result['STATUSCODE'] == 2){         //E("该用户认证未通过",2000);
                     M('face_detect')->where(array("user_id"=>$user_id,"ticket_id"=>$face_detect_result['ticket_id']))->save(array("status_code"=>2));
                     $result = $UploadYun->getStsInfo($user_id);
                     $VerifyToken = [
                         "Token" => $result['TOKEN'],
                         "DurationSeconds" => $result['DURATIONSECONDS'],
                     ];
                     file_put_contents("/tmp/33.log","aliyun--".date("Y-m-d H:i:s",time()).":".json_encode($VerifyToken)."".PHP_EOL,FILE_APPEND);
                     $this -> returnCode = 200;
                     $this -> returnData = $VerifyToken;
                 }
             }
         }catch(\Exception $e){
             $this -> returnCode = $e ->getCode();
             $this -> returnMsg = $e ->getMessage();
         }

         $this -> returnData();
     }

     /**
      * XML转数组
      * 数组格式 array('大写xml的tag'	=>	'xml的value');
      * 数组所有键为大写！！！-----重要！
      */
     private function xmlToArr($xml)
     {
         $parser = xml_parser_create();
         xml_parse_into_struct($parser, $xml, $data, $index);
         $arr = array();
         foreach ($data as $key => $value) {
             $arr[$value['tag']] = $value['value'];
         }
         return $arr;
     }
     /**
      * 通过图片的远程url，下载到本地
      * @param: $url为图片远程链接
      * @param: $filename为下载图片后保存的文件名
      */
     private function GrabImage($url,$filename) {
         if($url==""):return false;endif;
         ob_start();
         readfile($url);
         $img = ob_get_contents();
         ob_end_clean();
         $size = strlen($img);
         //"../../images/books/"为存储目录，$filename为文件名
         $fp2=@fopen("Public/Uploads/approvice/".$filename, "a");
         fwrite($fp2,$img);
         fclose($fp2);
         return $filename;
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
       public function createNameCard($token,$name=null,$card=null,$signature=null){
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