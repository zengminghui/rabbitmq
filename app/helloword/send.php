<?php
/**简单消息 */
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
//声明队列
/**
     * Declares queue, creates if needed
     *
     * @param string $queue        队列名
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
$channel->queue_declare('hello', false, false, false, false);
//设置超时
// $channel->queue_declare('test11', false, true, false, false, false, new AMQPTable(array(
//      "x-dead-letter-exchange" => "t_test1",
//      "x-message-ttl" => 15000,
//      "x-expires" => 16000
//   )));

//构造消息
$msgdata =  json_encode(array('a'=>'a1','b'=>'b1'));

// AMQPMessage 第2个数组参数 
//array(
//      'content_type' => 'shortstr',
//      'content_encoding' => 'shortstr',
//      'application_headers' => 'table_object',
//      'delivery_mode' => 'octet',
//      'priority' => 'octet',
//      'correlation_id' => 'shortstr',
//      'reply_to' => 'shortstr',
//      'expiration' => 'shortstr', 过期时间 1000=1秒
//      'message_id' => 'shortstr',
//      'timestamp' => 'timestamp',
//      'type' => 'shortstr',
//      'user_id' => 'shortstr',
//      'app_id' => 'shortstr',
//      'cluster_id' => 'shortstr',
//  );
$msg = new AMQPMessage($msgdata,array());
/****
 * 推送消息到队里
 * $msg 消息数据
 * ‘’命名为空字符串的默认交换机 交换机名称
 * hello routing_key 路由名称
 */
$channel->basic_publish($msg, '', 'hello');

echo " [x] Sent 'Hello World!'\n";
//关闭通道
$channel->close();
//关闭rabbitmq连接
$connection->close();