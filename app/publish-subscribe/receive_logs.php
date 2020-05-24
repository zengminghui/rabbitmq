<?php
/**发布/订阅 */
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
//连接rabbitmq服务器
$connection = new AMQPStreamConnection('192.168.101.101', 5672, 'admin', 'admin','/itcast');
//连接通道
$channel = $connection->channel();
//有几个可供选择的交换机类型：直连交换机（direct）, 主题交换机（topic）, （头交换机）headers和 扇型交换机（fanout）。
//我们在这里主要说明最后一个 —— 扇型交换机（fanout）。先创建一个fanout类型的交换机，命名为logs
$channel->exchange_declare('logs', 'fanout', false, false, false);
/**
 * 获取一个全新、空的临时队列，队列名由系统自动生成，当与消费者断开连接时这个队列应当被立即删
 */
//声明队列
/**
     * Declares queue, creates if needed
     *
     * @param string $queue
     * @param bool $passive
     * @param bool $durable        第3个参数ture 消息持久化 即rabbitmq崩溃或者退出消息会本地存储
     * @param bool $exclusive      独占通道
     * @param bool $auto_delete    自动删除
     * @param bool $nowait         不等待
     * @param array|\PhpAmqpLib\Wire\AMQPTable $arguments
     * @param int|null $ticket
     * @throws \PhpAmqpLib\Exception\AMQPTimeoutException if the specified operation timeout was exceeded
     * @return array|null
     */
list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);
/**
 * 交换器与队列绑定
 * $queue_name 队列名
 * 'logs' 交换机名
 * 第三个参数 是路由键
 */
$channel->queue_bind($queue_name, 'logs');

echo " [*] Waiting for logs. To exit press CTRL+C\n";

$callback = function ($msg) {
    echo ' [x] ', $msg->body, "\n";
};
/**
     * Starts a queue consumer
     *
     * @param string $queue         队列名
     * @param string $consumer_tag
     * @param bool $no_local
     * @param bool $no_ack
     * @param bool $exclusive
     * @param bool $nowait
     * @param callable|null $callback
     * @param int|null $ticket
     * @param array $arguments
     * @throws \PhpAmqpLib\Exception\AMQPTimeoutException if the specified operation timeout was exceeded
     * @return mixed|string
     */
$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
?>