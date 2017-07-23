<?php

namespace Liz\AliBundle\Controller;

use Liz\AliBundle\Aliyun\Api\Msg\lib\TokenGetterForAlicom;
use Liz\AliBundle\Aliyun\MNS\Exception\MnsException;
use Liz\AliBundle\Utils\Tool;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class DefaultController extends Controller
{

    /**
     * @Route("/send")
     */
    public function sendSmsAction()
    {
        $smsService = $this->get('liz.service.ali_sms');
        echo "SmsDemo::sendSms\n";
        $response = $smsService->sendSms(
            "李征", // 短信签名
            "SMS_78555002", // 短信模板编号
            "18701124322", // 短信接收者
            [
                // 短信模板中字段的值
                "code"=>"1234",
            ],
            "123" //流水号
        );
        dump($response);
        return $this->render('LizAliBundle:Default:index.html.twig');
    }

    /**
     * @Route("/query")
     */
    public function queryDetailsAction(){
        $smsService = $this->get('liz.service.ali_sms');
        echo "SmsDemo::queryDetails\n";
        $response = $smsService->queryDetails(
            "18701124322"
        );
        return $this->render('LizAliBundle:Default:index.html.twig');
    }

    /**
     * @Route("/receive")
     */
    public function receiveMsgAction(){
        echo "MsgDemo::receiveMsg SmsReport\n";
        $this->receiveMsg(
        // string $messageType 消息类型: SmsReport | SmsUp
            "SmsUp",

            // string $queueName 在云通信页面开通相应业务消息后，就能在页面上获得对应的queueName
            "Alicom-Queue-1369784437498729-SmsUp",

            /**
             * 回调
             * @param stdClass $message 消息数据
             * @return bool 返回true，则工具类自动删除已拉取的消息。返回false，消息不删除可以下次获取
             */
            function ($message) {
                print_r($message);
                return false;
            }
        );
        return $this->render('LizAliBundle:Default:index.html.twig');

    }


    /**
     * 获取消息
     *
     * @param string $messageType 消息类型: SmsReport | SmsUp
     * @param string $queueName 在云通信页面开通相应业务消息后，就能在页面上获得对应的queueName<br/>(e.g. Alicom-Queue-xxxxxx-SmsReport)
     * @param callable $callback <p>
     * 回调仅接受一个消息参数;
     * <br/>回调返回true，则工具类自动删除已拉取的消息;
     * <br/>回调返回false,消息不删除可以下次获取.
     * <br/>(e.g. function ($message) { return true; }
     * </p>
     */
    public function receiveMsg($messageType, $queueName, callable $callback)
    {

        $tokenGetter = new TokenGetterForAlicom(
            "1369784437498729",
            "LTAIVwaFWpQh10NO",
            "mBhckTeFV2HfQLYGoE3iJavT45ZhMh",
            $this->get('liz.utils.tool')
        );

        $i = 0;
        // 取回执消息失败3次则停止循环拉取
        while ( $i < 3) {
            try
            {
                // 取临时token
                $tokenForAlicom = $tokenGetter->getTokenByMessageType($messageType, $queueName);
                // 使用MNSClient得到Queue
                $queue = $tokenForAlicom->getClient()->getQueueRef($queueName);

                // 接收消息，并根据实际情况设置超时时间
                $res = $queue->receiveMessage(2);

                // 计算消息体的摘要用作校验
                $bodyMD5 = strtoupper(md5(base64_encode($res->getMessageBody())));

                // 比对摘要，防止消息被截断或发生错误
                if ($bodyMD5 == $res->getMessageBodyMD5())
                {
                    // 执行回调
                    if(call_user_func($callback, json_decode($res->getMessageBody())))
                    {
                        // 当回调返回真值时，删除已接收的信息
                        $receiptHandle = $res->getReceiptHandle();
                        $queue->deleteMessage($receiptHandle);
                    }
                }

                return; // 整个取回执消息流程完成后退出
            }
            catch (MnsException $e)
            {
                $i++;
                dump($e);
                echo "<br>";
            }
        }
    }

    /**
     * @Route("/pay")
     */
    public function pay(){
        $this->get('liz.service.ali_pay')
            ->pay();

        return $this->render('LizAliBundle:Default:index.html.twig');
    }
}
