<?php
return array(
    // 数据库常用配置
    'DB_TYPE'           =>  'mysqli',               // 数据库类型
//  'DB_TYPE'           =>  'mysql',                // 数据库类型

//    'DB_HOST'         =>  'mtestapi.57xun.com',           // 数据库服务器地址
    'DB_HOST'           =>  '127.0.0.1',            // 数据库服务器地址
    'DB_NAME'           =>  'muatest',          // 数据库名
    'DB_USER'           =>  'muatest',                  // 数据库用户名
    'DB_PWD'            =>  'bb4e0871ce094237',                     // 指向本地数据库密码

    'DB_PORT'           =>  3306,                   // 数据库端口
    'DB_PREFIX'         =>  'zb_',                  // 数据库表前缀
    'REDIS_HOST'            => '127.0.0.1',             //redis地址
    'REDIS_PORT'            => '6379',                  //redis端口
    'REDIS_PWD'         => 'Etang123',              //redis密码
'DB_CHARSET'        =>  'utf8mb4',                  // 数据库编码
    'DB_FIELDS_CACHE'   =>  true,                   // 启用字段缓存
    'SALT' => 'hello_world',                    //token规则
    'APP_URLS'       =>'http://mtestapi.57xun.com',   //域名地址
    'APP_URL'       =>'http://img.57xun.com',   //域名地址
    'Image_URL'       =>'http://mtestadm.57xun.com',   //域名地址
    'APP_IP'            => 'http://127.0.0.1/MasterServer/Master/GetGate',             //socket地址
    'APP_PORT'          => '80',                  //socket端口
'REDISDB'          => 1,
'ISLOGIN'          =>  0,
'APP_URL_image'       =>'http://img.57xun.com',   //域名地址oss
'APP_URL_image_web'       =>'http://img.muayy.com',   //域名地址oss
'IMG_URL'       =>'http://img.57xun.com',   //域名地址oss
//'IMG_URL'       =>'http://img.muayy.com',   //域名地址oss
    
        'OPEN_WEIXIN' => array(
        /*'APPID' => 'wx03300c4f2f798086',
         "MCHID" => '1505652251',
         'APIKEY'=> '0bc9a68ac5b8dca13f2b99c0174f0ccb',
         'APPSECRET'=> 'c2aebc30e797822c2a068b1946bf5427',*/
        'APPID' => 'wxa7e9bf6ada80ceb3',
        "MCHID" => '1513946181',
        //            'APIKEY'=> '2248a6c78903f24f96402554ecf2fdbb',
        'APIKEY'=> '3d82f2a22ee2e440dc2d4ac8b5806ec9',
        //            'APPSECRET'=> '5c170594915d7c577e34d99566d12a68',
        'APPSECRET'=> 'f06cd04649d94c36cd9cce16861fe055',
        'PLACE_ORDER'=>'https://api.mch.weixin.qq.com/pay/unifiedorder',
//        'NOTIFY_URL'=> 'http://mapi.57xun.com/index.php/Api/WeiXin/weixinNotify',
            'NOTIFY_URL'=> 'http://mapi.57xun.com/index.php/Api/WeiXin/weiXinNotify',
        'CASH_HTTPS'=>'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers',
        'SSLCERT_PATH' => realpath(__DIR__.'/../').'/Key1/apiclient_cert.pem',
        'SSLKEY_PATH' => realpath(__DIR__.'/../').'/Key1/apiclient_key.pem',
        'CHECK_NAME' => 'OPTION_CHECK',
    ),
    'ALIPAY' => array(
        'APP_ID'=>'2018061360348344',
        'SUBJECT'=>'MUA语音',
        'SERVICE'=>'mobile.securitypay.pay',
        'PARTNER'=>'2088131391400150',
        'INPUT_CHARSET'=>'utf-8',
        'SELLER_ID'=>'',
        'TRANSPORT'=>'http',
        'NOTIFY_URL'=>'http://mapi.57xun.com/index.php/Api/AliPay/aliNotify',
        'HTTP_VERIFY_URL'=>'http://notify.alipay.com/trade/notify_query.do?',
        'HTTTS_VERIFY_URL'=>'https://mapi.alipay.com/gateway.do?service=notify_verify&',
        'CACERT'=> realpath(__DIR__.'/../').'/Key/cacert.pem',
    ),

    'WECHAT_OPEN'=> array(//公众号
            'APPID' => 'wxe50b8b85af03082a',
            "MCHID" => '1543515161',
            'APIKEY'=> '5c4a81648ccc8342ce21bbd8c5e590f8',
            'APPSECRET'=> 'b39d6b56c50a7b9bf03bc80c067a4547',
            'PLACE_ORDER'=>'https://api.mch.weixin.qq.com/pay/unifiedorder',
            'NOTIFY_URL'=> 'http://mtestapi.57xun.com/index.php/Api/WechatPublic/weiXinBack',
            'CASH_HTTPS'=>'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers',
            'SSLCERT_PATH' => realpath(__DIR__.'/../').'/Key1/apiclient_cert.pem',
            'SSLKEY_PATH' => realpath(__DIR__.'/../').'/Key1/apiclient_key.pem',
            'CHECK_NAME' => 'PUBLIC_CHECK',
        ),
    
    'APPLE' => array(
        'ENV'=>'sandbox',
        'VERIFY_URL_SANDBOX'=>'https://sandbox.itunes.apple.com/verifyReceipt',
        'VERIFY_URL_PRODUCT'=>'https://buy.itunes.apple.com/verifyReceipt',
    ),
    /* 模块相关配置 */
    'DEFAULT_MODULE'        => 'Home',

    'MODULE_DENY_LIST'      => array('Common'),

    /* URL配置 */
    'URL_CASE_INSENSITIVE'  => false,   // 默认false 表示URL区分大小写 true则表示不区分大小写，修改会导致权限验证失败
    'URL_MODEL'             => 0,       // URL模式
    'VAR_URL_PARAMS'        => '',      // PATH_INFO URL参数变量
    'URL_PATHINFO_DEPR'     => '/',     // PATH_INFO URL分割符
    'DEFAULT_MODULE'        =>  'Home',  // 默认模块

    'URL_MODEL'              =>2,
    'URL_HTML_SUFFIX' => '',
    "SEND_MSG_SECRET_KEY"=> '02a75c4d0dd30025bfa3ee99df2600e0',
    "LSM_SIGN"=> '（动态验证码，请勿泄露，十分钟内有效）如非本人操作，请忽略本短信.【Mua】',

    //阿里云OSS存储图片
    'OSS' => array(
        'ACCESS_KEY_ID' => 'LTAIB1xELc9MzLx5', //从OSS获得的AccessKeyId
        'ACCESS_KEY_SECRET' => 'fMoCSlTQfoHDagZa45zfxgqfA6eNHS', //从OSS获得的AccessKeySecret
        'ENDPOINT' => 'http://oss-cn-zhangjiakou.aliyuncs.com', //您选定的OSS数据中心访问域名，例如oss-cn-hangzhou.aliyuncs.com
        'BUCKET'=>'muatest'
    ),
   'APPLEPAY_URL_DEV' => "https://sandbox.itunes.apple.com/verifyReceipt", 
   'APPLEPAY_URL' => "https://buy.itunes.apple.com/verifyReceipt",
   
   'STSCONF' =>  array(
        //"AccessKeyID" => "LTAIWDuY7Omo3ITY",
        //"AccessKeySecret" => "P3libDDVGbz6kKWPg3oghj0Pfr1yhB",
'AccessKeyID' => 'LTAIB1xELc9MzLx5',
'AccessKeySecret' => 'fMoCSlTQfoHDagZa45zfxgqfA6eNHS',         

"RoleArn" => "acs:ram::1313844168009472:role/oss",
        "BucketName" => "muatest",
        "Endpoint" => "oss-cn-zhangjiakou.aliyuncs.com",
        "TokenExpireTime" => "3600",
        "PolicyFile"=> '{
            "Statement": [
                {
                  "Action": [
                    "oss:*"
                  ],
                  "Effect": "Allow",
                  "Resource": ["acs:oss:*:*:*"]
                }
              ],
            "Version": "1"
        }',
//'voicepath'=>['pathtop'=>'muatest','path'=>'muavoice'],
'path'=>[
'voice'=>['pathtop'=>'muatest','path'=>'muavoice'],
'image'=>['pathtop'=>'muaimage','path'=>'test'],
'image1'=>['pathtop'=>'muaimage','path'=>'test'],
'image2'=>['pathtop'=>'muaimage','path'=>'test'],
],     
    ),
     'HXCONF' => array(
    'client_id'=>'YXA60rnPuYCUQxu6oIzEZnV80g',
    'client_secret'=>'YXA6ZwaKoKkiZuIyBea0DU_4o3qiQrM',
    'org_name'=>'1103190315168034',
    'app_name'=>'muatest'

    ),
'ALIYUNURL'=>'http://muatest.oss-cn-zhangjiakou.aliyuncs.com/',
'REALNAME'=>"3b1ab0c8b2324554a46b940464717e6c",
'AMQ' => array(
    'host'=>'172.26.29.176',
    'port'=>5672,
    'user'=>'admin1',
    'pwd'=>'admin1',

),
'MQTT' => [
        'instanceId' => 'post-cn-v641bb90k09',
        'endPointIn' => 'post-cn-v641bb90k09-internal.mqtt.aliyuncs.com',
        'endPoint' => 'post-cn-v641bb90k09.mqtt.aliyuncs.com',
        'accessKey' => 'LTAIB1xELc9MzLx5',
        'secretKey' => 'fMoCSlTQfoHDagZa45zfxgqfA6eNHS',
        'topic' => 'muatest',
        'groupId' => 'muatest_group',
        'tokenurl' => 'https://mqauth.aliyuncs.com',
    ],
'CLIENTOSS' => [
                'AccessKeyID'=>'LTAI4FcpvLog4BpWBbSid4qz',
                'AccessKeySecret'=>'4WY7HycF5OnPRnnZ0bDu7v1AexytGw',
                'pathtop'=>'muavoice',
                'path'=>'muavoice/test',
                'endpoint'=>'oss-cn-beijing.aliyuncs.com'
            ],
'FORUM' => false,
  
  

);
