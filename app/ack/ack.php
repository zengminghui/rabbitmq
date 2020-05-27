<?php
require_once __DIR__ . '/../../vendor/autoload.php';
// define('AMQP_DEBUG', true);
//生产者消息确认机制
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class ack_new{


    public function simpleSend(){
        //连接rq
        $conn = new AMQPStreamConnection('192.168.2.184',5672,'guest','guest');
        //建立通道
        $channel = $conn->channel();
        //确认投放队列，并将队列持久化
        $channel->queue_declare('hello', false, true, false, false);
        //异步回调消息确认
        $channel->set_ack_handler(
            function (AMQPMessage $message) {
                echo "Message acked with content发送成功 " . $message->body . PHP_EOL;
            }
        );
        $channel->set_nack_handler(
            function (AMQPMessage $message) {
                echo "Message nacked with content发送失败 " . $message->body . PHP_EOL;
            }
        );
        //开启消息确认
        $channel->confirm_select();
        //建立消息，并消息持久化
        $msg = new AMQPMessage('kingblanc!',array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
        $channel->basic_publish($msg, '', 'hello');
    
        echo " [x] Sent 'Hello World!'\n";
        //阻塞等待消息确认
        $channel->wait_for_pending_acks();
    
        $channel->close();
        $conn->close();
    }
    
    public function  fanoutSend(){
        $conn = new  AMQPStreamConnection('192.168.2.184',5672,'guest','guest');
        $channel = $conn -> channel();
        //创建交换机
        $channel -> exchange_declare('logs','fanout',false,true,false);
        //创建消息，并持久化
        $message = new AMQPMessage('hello every one',array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
    
        //消息发送状态回调
        $channel -> set_ack_handler(function (AMQPMessage $message){
            echo '发送成功::'.$message -> body;
        });
    
        $channel -> set_nack_handler(function (AMQPMessage $message){
            echo '发送失败::'.$message -> body;
        });
        //开启消息发送状态监听
        $channel -> confirm_select();
        //发送消息
        $channel -> basic_publish($message,'logs');
        //阻塞等待消息确认
        $channel -> wait_for_pending_acks();
    
        $channel -> close();
        $conn -> close();
    }
    
    public function  directSend(){
        $connection = new AMQPStreamConnection('192.168.2.184',5672,'guest','guest');
        $channel = $connection -> channel();
        $channel -> exchange_declare('direct_logs','direct',false,true,false);
        $message = new AMQPMessage('hello,balck',array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
    
        //消息发送状态回调
        $channel -> set_ack_handler(function (AMQPMessage $message){
            echo '发送成功::'.$message -> body;
        });
    
        $channel -> set_nack_handler(function (AMQPMessage $message){
            echo '发送失败::'.$message -> body;
        });
        //开启消息发送状态监听
        $channel -> confirm_select();
        //发送消息
        $channel -> basic_publish($message,'direct_logs','direct_black');
        //阻塞等待消息确认
        $channel -> wait_for_pending_acks();
    
        $channel -> close();
        $connection -> close();
    }
     
    
    //消费者:
    
    private function consumer(){
        $conn = new AMQPStreamConnection('192.168.2.184',5673,'guest','guest');
        $channel = $conn->channel();
    
        $channel->queue_declare('hello', false, true, false, false);
    
        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
    
        $callback = function($msg) {
            echo " [x] Received ", $msg->body, "\n";
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
    
        //开启消息确认模式
        $channel->basic_consume('hello', '', false, false, false, false, $callback);
    
        while(count($channel->callbacks)) {
            $channel->wait();
        }
    
        $channel->close();
        $conn->close();
    }
    
    //消息争抢模式
    private function consumer_bos(){
        $conn = new AMQPStreamConnection('192.168.2.184',5672,'guest','guest');
        $channel = $conn->channel();
    
        $channel->queue_declare('hello', false, true, false, false);
    
        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
    
        $callback = function($msg) {
            echo " [x] Received ", $msg->body, "\n";
            sleep(3);
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
    
        //开启消息确认模式
        $channel->basic_consume('hello', '', false, false, false, false, $callback);
        //开启消息争抢
        $channel->basic_qos(null, 1, null);
        while(count($channel->callbacks)) {
            $channel->wait();
        }
    
        $channel->close();
        $conn->close();
    }
    
    //fanout消息队列监听
    private  function  consumer_fanout(){
        $conn = new AMQPStreamConnection('192.168.2.184',5672,'guest','guest');
        $channel = $conn->channel();
        $channel->exchange_declare('logs', 'fanout', false, true, false);
        //获取临时队列
        list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);
        //绑定队列到交换机
        $channel->queue_bind($queue_name, 'logs');
    
        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
    
        $callback = function($msg) {
            echo " [x] Received ", $msg->body, "\n";
            sleep(3);
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
    
        //开启消息确认模式
        $channel->basic_consume($queue_name, '', false, false, false, false, $callback);
    
        while(count($channel->callbacks)) {
            $channel->wait();
        }
    
        $channel->close();
        $conn->close();
    }
    
    //fanout消息队列监听
    private  function  consumer_direct(){
        $conn = new AMQPStreamConnection('192.168.2.184',5672,'guest','guest');
        $channel = $conn->channel();
        $channel-> exchange_declare('direct_logs', 'direct', false, true, false);
    
        //获取临时队列
        list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);
        //绑定队列到交换机
        $channel->queue_bind($queue_name, 'direct_logs','direct_black');
    
        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
    
        $callback = function($msg) {
            echo " [x] Received ", $msg->body, "\n";
            sleep(3);
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };
    
        //开启消息确认模式
        $channel->basic_consume($queue_name, '', false, false, false, false, $callback);
    
        while(count($channel->callbacks)) {
            $channel->wait();
        }
    
        $channel->close();
        $conn->close();
    }
    ————————————————
    版权声明：本文为CSDN博主「hello白白」的原创文章，遵循CC 4.0 BY-SA版权协议，转载请附上原文出处链接及本声明。
    原文链接：https://blog.csdn.net/u010229677/java/article/details/103788412

}