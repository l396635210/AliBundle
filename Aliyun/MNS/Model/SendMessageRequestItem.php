<?php
namespace Liz\AliBundle\Aliyun\MNS\Model;

use Liz\AliBundle\Aliyun\MNS\Traits\MessagePropertiesForSend;

// this class is used for BatchSend
class SendMessageRequestItem
{
    use MessagePropertiesForSend;

    public function __construct($messageBody, $delaySeconds = NULL, $priority = NULL)
    {
        $this->messageBody = $messageBody;
        $this->delaySeconds = $delaySeconds;
        $this->priority = $priority;
    }

    public function writeXML(\XMLWriter $xmlWriter, $base64)
    {
        $xmlWriter->startELement('Message');
        $this->writeMessagePropertiesForSendXML($xmlWriter, $base64);
        $xmlWriter->endElement();
    }
}

?>
