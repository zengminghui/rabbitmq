<?php
/**简单消息 */
require_once __DIR__ . '/../../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
//连接rabbitmq服务器
$connection = new AMQPStreamConnection('192.168.101.101', 5672, 'admin', 'admin','/itcast');
//连接通道
$channel = $connection->channel();
//声明队列 第3个参数ture 消息持久化 即rabbitmq崩溃或者退出消息会本地存储
$channel->queue_declare('hello', false, false, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";
//监听消息回调函数
$callback = function ($msg) {
    $receive = json_decode($msg->body);
    echo ' 接受到的数据', var_export($receive,true) , "\n";
};
//消费hello通道
$channel->basic_consume('hello', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    //等待消息
    $channel->wait();
}

//关闭通道
$channel->close();
//关闭rabbitmq连接
$connection->close();;