<?php
/**发布/订阅 */
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

//连接rabbitmq服务器
$connection = new AMQPStreamConnection('192.168.101.101', 5672, 'admin', 'admin','/itcast');
//连接通道
$channel = $connection->channel();

//有几个可供选择的交换机类型：直连交换机（direct）, 主题交换机（topic）, （头交换机）headers和 扇型交换机（fanout）。
//我们在这里主要说明最后一个 —— 扇型交换机（fanout）。先创建一个fanout类型的交换机，命名为logs

$channel->exchange_declare('logs', 'fanout', false, false, false);

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = "info: Hello World!";
}
$msg = new AMQPMessage($data);
/****
 * 推送消息到队里
 * $msg 消息数据
 * ‘’命名为空字符串的默认交换机 交换机名称
 * hello routing_key 路由名称
 */
$channel->basic_publish($msg, 'logs');

echo ' [x] Sent ', $data, "\n";

$channel->close();
$connection->close();
?>