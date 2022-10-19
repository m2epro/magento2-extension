<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Issue\Notification\Channel\Magento;

use Ess\M2ePro\Controller\Adminhtml\Base;
use Ess\M2ePro\Model\Exception\Logic;
use Ess\M2ePro\Model\Issue\DataObject;
use Ess\M2ePro\Model\Issue\Notification\ChannelInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface as Message;

class Session implements ChannelInterface
{
    /** @var ManagerInterface */
    private $messageManager;

    /**
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(ManagerInterface $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    /**
     * @inheritDoc
     *
     * @throws Logic
     */
    public function addMessage(DataObject $message): void
    {
        switch ($message->getType()) {
            case Message::TYPE_NOTICE:
                $this->messageManager->addNotice(
                    $message->getText(),
                    Base::GLOBAL_MESSAGES_GROUP
                );
                break;

            case Message::TYPE_SUCCESS:
                $this->messageManager->addSuccess(
                    $message->getText(),
                    Base::GLOBAL_MESSAGES_GROUP
                );
                break;

            case Message::TYPE_WARNING:
                $this->messageManager->addWarning(
                    $message->getText(),
                    Base::GLOBAL_MESSAGES_GROUP
                );
                break;

            case Message::TYPE_ERROR:
                $this->messageManager->addError(
                    $message->getText(),
                    Base::GLOBAL_MESSAGES_GROUP
                );
                break;

            default:
                throw new Logic(
                    sprintf('Unsupported message type [%s]', $message->getType())
                );
        }
    }
}
