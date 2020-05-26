<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Issue\Notification\Channel\Magento;

use \Ess\M2ePro\Model\AbstractModel;
use \Ess\M2ePro\Model\Issue\Notification\Channel\ChannelInterface;
use \Magento\Framework\Notification\MessageInterface as AdminNotification;
use \Magento\Framework\Message\MessageInterface as Message;

/**
 * Class \Ess\M2ePro\Model\Issue\Notification\Channel\Magento\GlobalMessage
 */
class GlobalMessage extends AbstractModel implements ChannelInterface
{
    //########################################

    public function addMessage(\Ess\M2ePro\Model\Issue\DataObject $issue)
    {
        $typesMapping = [
            Message::TYPE_NOTICE  => AdminNotification::SEVERITY_NOTICE,
            Message::TYPE_SUCCESS => AdminNotification::SEVERITY_MINOR,
            Message::TYPE_WARNING => AdminNotification::SEVERITY_MAJOR,
            Message::TYPE_ERROR   => AdminNotification::SEVERITY_CRITICAL
        ];

        $this->getHelper('Magento')->addGlobalNotification(
            $issue->getTitle(),
            strip_tags($issue->getText()),
            isset($typesMapping[$issue->getType()]) ? $typesMapping[$issue->getType()] : null,
            $issue->getUrl()
        );
    }

    //########################################
}
