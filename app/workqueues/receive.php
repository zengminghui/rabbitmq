<?php
require_once __DIR__ . '/../../vendor/autoload.php';
//实现延迟队列
//具体原理是新建两条队列绑定对应的交换机，其中一条设置消息延迟执行，在到期后使用交换机丢到另一个交换机中，
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;
$connection = new AMQPStreamConnection('192.168.101.101', 5672, 'admin', 'admin','/itcast');
$channel = $connection->channel();
//声明交换机
$channel->exchange_declare('delay_exchange', 'direct',false,false,false);
// $channel->exchange_declare('cache_exchange', 'direct',false,false,false);

//声明队列
$channel->queue_declare('delay_queue',false,true,false,false,false);
$channel->queue_bind('delay_queue', 'delay_exchange','delay_exchange');

echo ' [*] Waiting for message. To exit press CTRL+C '.PHP_EOL;

$callback = function ($msg){
    echo date('Y-m-d H:i:s')." [x] Received",$msg->body,PHP_EOL;

     $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

};

//只有consumer已经处理并确认了上一条message时queue才分派新的message给它
$channel->basic_qos(null, 1, null);
//消费队列  其他交换机投递过期消息到delay_exchange交换机，交换机将消息投递到 delay_queue,至此我们实现了延迟消费
//场景 "课程开启后十分钟推送消息”,"订单生成后多少分钟自动取消"，"会员还有3天到期发送提醒"
$channel->basic_consume('delay_queue','',false,false,false,false,$callback);


while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();
