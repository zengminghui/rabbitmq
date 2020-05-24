<?php
require_once __DIR__ . '/../../vendor/autoload.php';
//实现延迟队列
//具体原理是新建两条队列绑定对应的交换机，其中一条设置消息延迟执行，在到期后使用交换机丢到另一个交换机中，
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

$connection = new AMQPStreamConnection('192.168.101.101', 5672, 'admin', 'admin','/itcast');
$channel = $connection->channel();
//给cache发送  使其过期然后定向到另一个
//声明两个队列
$channel->exchange_declare('delay_exchange', 'direct',false,false,false); //实现延迟交换机
$channel->exchange_declare('cache_exchange', 'direct',false,false,false);

$tale = new AMQPTable();
$tale->set('x-dead-letter-exchange', 'delay_exchange');     //exchange很关键  表示过期后由哪个exchange处理
$tale->set('x-dead-letter-routing-key','delay_exchange');   //routing-key  表示过期后由哪个exchange处理
$tale->set('x-message-ttl',10000);                          //存活时长   下面的过期时间不能超过

$channel->queue_declare('cache_queue',false,true,false,false,false,$tale);
$channel->queue_bind('cache_queue', 'cache_exchange','cache_exchange');

$channel->queue_declare('delay_queue',false,true,false,false,false);
$channel->queue_bind('delay_queue', 'delay_exchange','delay_exchange');


$msg = new AMQPMessage('Hello World9000',array(
    'expiration' => 9000,                               //这条消息的存活时间
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT

));

$channel->basic_publish($msg,'cache_exchange','cache_exchange');
echo date('Y-m-d H:i:s')." [x] Sent 'Hello World!' ".PHP_EOL;

$channel->close();
$connection->close();
