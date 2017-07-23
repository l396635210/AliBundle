<?php
namespace Liz\AliBundle\Aliyun\MNS\Exception;

use Liz\AliBundle\Aliyun\MNS\Constants;
use Liz\AliBundle\Aliyun\MNS\Model\DeleteMessageErrorItem;

/**
 * BatchDelete could fail for some receipt handles,
 *     and BatchDeleteFailException will be thrown.
 * All failed receiptHandles are saved in "$deleteMessageErrorItems"
 */
class BatchDeleteFailException extends MnsException
{
    protected $deleteMessageErrorItems;

    public function __construct($code, $message, $previousException = NULL, $requestId = NULL, $hostId = NULL)
    {
        parent::__construct($code, $message, $previousException, Constants::BATCH_DELETE_FAIL, $requestId, $hostId);

        $this->deleteMessageErrorItems = array();
    }

    public function addDeleteMessageErrorItem(DeleteMessageErrorItem $item)
    {
        $this->deleteMessageErrorItems[] = $item;
    }

    public function getDeleteMessageErrorItems()
    {
        return $this->deleteMessageErrorItems;
    }
}

?>
