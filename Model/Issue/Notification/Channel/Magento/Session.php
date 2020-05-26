<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Issue\Notification\Channel\Magento;

use \Ess\M2ePro\Model\AbstractModel;
use \Ess\M2ePro\Model\Issue\Notification\Channel\ChannelInterface;
use \Magento\Framework\Message\MessageInterface as Message;

/**
 * Class \Ess\M2ePro\Model\Issue\Notification\Channel\Magento\Session
 */
class Session extends AbstractModel implements ChannelInterface
{
    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->messageManager = $messageManager;

        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function addMessage(\Ess\M2ePro\Model\Issue\DataObject $issue)
    {
        switch ($issue->getType()) {
            case Message::TYPE_NOTICE:
                $this->messageManager->addNotice(
                    $issue->getText(),
                    \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP
                );
                break;

            case Message::TYPE_SUCCESS:
                $this->messageManager->addSuccess(
                    $issue->getText(),
                    \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP
                );
                break;

            case Message::TYPE_WARNING:
                $this->messageManager->addWarning(
                    $issue->getText(),
                    \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP
                );
                break;

            case Message::TYPE_ERROR:
                $this->messageManager->addError(
                    $issue->getText(),
                    \Ess\M2ePro\Controller\Adminhtml\Base::GLOBAL_MESSAGES_GROUP
                );
                break;

            default:
                throw new \Ess\M2ePro\Model\Exception\Logic(
                    sprintf('Unsupported message type [%s]', $issue->getType())
                );
        }
    }

    //########################################
}
