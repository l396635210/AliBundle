<?php

namespace Liz\AliBundle\Services;

use Liz\AliBundle\Aliyun\Api\Msg\lib\TokenGetterForAlicom;
use Liz\AliBundle\Aliyun\Core\Config;
use Liz\AliBundle\Aliyun\Core\Exception\ClientException;
use Liz\AliBundle\Aliyun\Core\Profile\DefaultProfile;
use Liz\AliBundle\Aliyun\Core\DefaultAcsClient;
use Liz\AliBundle\Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Liz\AliBundle\Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;
use Liz\AliBundle\Aliyun\MNS\Exception\MnsException;
use Liz\AliBundle\Utils\Tool;

/**
 * Created by PhpStorm.
 * User: Lizheng
 * Date: 2017/7/22
 * Time: 5:35
 */
class SmsService
{
    private $accessKeyId;
    private $accessKeySecret;
    private $accountId = "";
    private $tool;

    private $acsClient;

    private static $receiveMsgSmsReport = "SmsReport";

    private static $receiveMsgSmsUp     = "SmsUp";

    /**
     * @return string
     */
    public static function getReceiveMsgSmsReport()
    {
        return self::$receiveMsgSmsReport;
    }

    /**
     * @return string
     */
    public static function getReceiveMsgSmsUp()
    {
        return self::$receiveMsgSmsUp;
    }

    /**
     * SmsService constructor.
     * @param $accessKeyId
     * @param $accessKeySecret
     * @param Tool $tool
     * @param $accountId 阿里云控制板-账号管理-安全设置-账号id
     */
    public function __construct($accessKeyId, $accessKeySecret, Tool $tool, $accountId)
    {
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->tool = $tool;
        $this->accountId = $accountId;
        Config::load();
        // 短信API产品名
        $product = "Dysmsapi";
        // 短信API产品域名
        $domain = "dysmsapi.aliyuncs.com";
        // 暂时不支持多Region
        $region = "cn-hangzhou";
        // 服务结点
        $endPointName = "cn-hangzhou";
        // 初始化用户Profile实例
        $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
        // 增加服务结点
        DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);
        // 初始化AcsClient用于发起请求
        $this->acsClient = new DefaultAcsClient($profile, $this->tool);
    }

    /**
     * 发送短信范例
     *
     * @param string $signName <p>
     * 必填, 短信签名，应严格"签名名称"填写，参考：<a href="https://dysms.console.aliyun.com/dysms.htm#/sign">短信签名页</a>
     * </p>
     * @param string $templateCode <p>
     * 必填, 短信模板Code，应严格按"模板CODE"填写, 参考：<a href="https://dysms.console.aliyun.com/dysms.htm#/template">短信模板页</a>
     * (e.g. SMS_0001)
     * </p>
     * @param string $phoneNumbers 必填, 短信接收号码 (e.g. 12345678901)
     * @param array|null $templateParam <p>
     * 选填, 假如模板中存在变量需要替换则为必填项 (e.g. Array("code"=>"12345", "product"=>"阿里通信"))
     * </p>
     * @param string|null $outId [optional] 选填, 发送短信流水号 (e.g. 1234)
     * @return mixed|\SimpleXMLElement
     */
    public function sendSms($signName, $templateCode, $phoneNumbers, $templateParam = null, $outId = null) {
        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();
        // 必填，设置雉短信接收号码
        $request->setPhoneNumbers($phoneNumbers);
        // 必填，设置签名名称
        $request->setSignName($signName);
        // 必填，设置模板CODE
        $request->setTemplateCode($templateCode);
        // 可选，设置模板参数
        if($templateParam) {
            $request->setTemplateParam(json_encode($templateParam));
        }
        // 可选，设置流水号
        if($outId) {
            $request->setOutId($outId);
        }
        // 发起访问请求
        $acsResponse = $this->acsClient->getAcsResponse($request);
        if($acsResponse->Code!="OK"){
            $this->createSmsException($acsResponse);
        }
        // 打印请求结果
        // var_dump($acsResponse);
        return $acsResponse;

    }


    /**
     * 查询短信发送情况范例
     *
     * @param string $phoneNumbers 必填, 短信接收号码 (e.g. 12345678901)
     * @param string $sendDate 必填，短信发送日期，格式Ymd，支持近30天记录查询 (e.g. 20170710)
     * @param int $pageSize 必填，分页大小
     * @param int $currentPage 必填，当前页码
     * @param string $bizId 选填，短信发送流水号 (e.g. abc123)
     * @return mixed|\SimpleXMLElement
     */
    public function queryDetails($phoneNumbers, \DateTime $sendDate=null, $pageSize = 10, $currentPage = 1, $bizId=null) {
        $sendDate = $sendDate ? $sendDate : new \DateTime('now');
        // 初始化QuerySendDetailsRequest实例用于设置短信查询的参数
        $request = new QuerySendDetailsRequest();
        // 必填，短信接收号码
        $request->setPhoneNumber($phoneNumbers);
        // 选填，短信发送流水号
        $request->setBizId($bizId);
        // 必填，短信发送日期，支持近30天记录查询，格式Ymd
        $request->setSendDate($sendDate->format("Ymd"));
        // 必填，分页大小
        $request->setPageSize($pageSize);
        // 必填，当前页码
        $request->setCurrentPage($currentPage);
        // 发起访问请求
        $acsResponse = $this->acsClient->getAcsResponse($request);
        // 打印请求结果
        if($acsResponse->Code!="OK"){
            $this->createSmsException($acsResponse);
        }

        return $acsResponse;
    }

    /**
     * 获取消息
     *
     * @param string $messageType 消息类型: SmsReport | SmsUp
     * @param callable $callback <p>
     * 回调仅接受一个消息参数;
     * <br/>回调返回true，则工具类自动删除已拉取的消息;
     * <br/>回调返回false,消息不删除可以下次获取.
     * <br/>(e.g. function ($message) { return true; }
     * </p>
     */
    public function receiveMsg($messageType, callable $callback)
    {
        $tokenGetter = new TokenGetterForAlicom(
            $this->accountId,
            $this->accessKeyId,
            $this->accessKeySecret,
            $this->tool
        );
dump($tokenGetter);
        $queueName = "Alicom-Queue-".$this->accountId."-".$messageType;
        $i = 0;
        // 取回执消息失败3次则停止循环拉取
        while ( $i < 3) {
            try
            {
                // 取临时token
                $tokenForAlicom = $tokenGetter->getTokenByMessageType($messageType, $queueName);
                dump($tokenForAlicom->getClient());die;
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


    protected function createSmsException(\stdClass $response){
        throw new ClientException($response->Message, $response->Code);
    }

}