<?php
/**队列 */
require_once __DIR__ . '/../../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
//连接rabbitmq服务器
//AMQPStreamConnection 初始化参数
/**
     * @param string $host 域名或者IP
     * @param string $port 端口
     * @param string $user 账号
     * @param string $password 密码
     * @param string $vhost 虚拟机地址
     * @param bool $insist 
     * @param string $login_method
     * @param null $login_response @deprecated
     * @param string $locale
     * @param float $connection_timeout
     * @param float $read_write_timeout
     * @param null $context
     * @param bool $keepalive
     * @param int $heartbeat
     * @param float $channel_rpc_timeout
     * @param string|null $ssl_protocol
     */
$connection = new AMQPStreamConnection('192.168.101.101', 5672, 'admin', 'admin','/itcast');
//连接通道
$channel = $connection->channel();
//声明队列 第3个参数ture 消息持久化 即rabbitmq崩溃或者退出消息会本地存储

$channel->queue_declare('task_queue', false, true, false, false);

echo " [*] Waiting for messages. To exit press CTRL+C\n";

$callback = function ($msg) {
    echo ' [x] Received ', $msg->body, "\n";
    sleep(substr_count($msg->body, '.'));
    echo " [x] Done\n";
    //因为no_ack=false 所以需要进行ack应答，不应答可能会出现内存不足
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};
//第2个参数 prefetch_count=1 只接受一条消息，直到处理完成并且响应后才会发送下一条消息过来，保证公平调度。
$channel->basic_qos(null, 1, null);
//消费者 第四个参数默认 no_ack=false 设置为ture 表示不响应ack 。消费者需要在任务处理完成后发送消息响应给rabbitmq服务。
//如果消费者（consumer）挂掉了，没有发送响应，RabbitMQ就会认为消息没有被完全处理，然后重新发送给其他消费者（consumer）。
//这样，及时工作者（workers）偶尔的挂掉，也不会丢失消息
/**
     * Starts a queue consumer
     *
     * @param string $queue         队列名
     * @param string $consumer_tag
     * @param bool $no_local
     * @param bool $no_ack      no_ack = false 时，表示进行ack应答，确保消息已经处理
     * @param bool $exclusive
     * @param bool $nowait
     * @param callable|null $callback
     * @param int|null $ticket
     * @param array $arguments
     * @throws \PhpAmqpLib\Exception\AMQPTimeoutException if the specified operation timeout was exceeded
     * @return mixed|string
     */
$channel->basic_consume('task_queue', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
?>