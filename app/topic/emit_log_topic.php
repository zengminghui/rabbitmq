
<?php

require_once __DIR__ . '/../../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

//连接rabbitmq服务器
$connection = new AMQPStreamConnection('192.168.101.101', 5672, 'admin', 'admin','/itcast');
//连接通道
$channel = $connection->channel();

$channel->exchange_declare('topic_logs', 'topic', false, false, false);

$routing_key = isset($argv[1]) && !empty($argv[1]) ? $argv[1] : 'anonymous.info';
$data = implode(' ', array_slice($argv, 2));
if (empty($data)) {
    $data = "Hello World!";
}

$msg = new AMQPMessage($data);
/****
 * 推送消息到队里
 * $msg 消息数据
 * logs 交换机名称
 *  routing_key 路由键
 * 发送到主题交换机（topic exchange）的消息不可以携带随意什么样子的路由键（routing_key），它的路由键必须是一个由.分隔开的词语列表
 * 几个推荐的例子："stock.usd.nyse", "nyse.vmw", "quick.orange.rabbit"
 */
$channel->basic_publish($msg, 'topic_logs', $routing_key);

echo ' [x] Sent ', $routing_key, ':', $data, "\n";

$channel->close();
$connection->close();
?>