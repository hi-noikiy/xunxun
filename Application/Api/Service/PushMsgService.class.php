<?php

namespace Api\Service;
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
use Think\Service;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AMQPStrea;
use PhpAmqpLib\Message\AMQPMessage;

class PushMsgService extends Service {


public function send($queue='',$content=''){
	$conf = C('AMQ');
	$connection = new AMQPStreamConnection($conf['host'], $conf['port'], $conf['user'], $conf['pwd']);
	$channel = $connection->channel();
	$channel->queue_declare($queue, false, false, false, false);
	
	//消息内容
	$msg = new AMQPMessage($content);
	$channel->basic_publish($msg, '', $queue);

	//关闭连接  
	$channel->close();
	$connection->close();
	

	}



}
