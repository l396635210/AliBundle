<?php
namespace Liz\AliBundle\Aliyun\MNS\Traits;

use Liz\AliBundle\Aliyun\MNS\Constants;

trait MessagePropertiesForPublish
{
    public $messageBody;
    public $messageAttributes;

    public function getMessageBody()
    {
        return $this->messageBody;
    }

    public function setMessageBody($messageBody)
    {
        $this->messageBody = $messageBody;
    }

    public function getMessageAttributes()
    {
        return $this->messageAttributes;
    }

    public function setMessageAttributes($messageAttributes)
    {
        $this->messageAttributes = $messageAttributes;
    }

    public function writeMessagePropertiesForPublishXML(\XMLWriter $xmlWriter)
    {
        if ($this->messageBody != NULL)
        {
            $xmlWriter->writeElement(Constants::MESSAGE_BODY, $this->messageBody);
        }
        if ($this->messageAttributes !== NULL)
        {
            $this->messageAttributes->writeXML($xmlWriter);
        }
    }
}

?>
