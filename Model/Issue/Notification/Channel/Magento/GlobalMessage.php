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
    /** @var \Magento\AdminNotification\Model\InboxFactory */
    protected $notificationFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\AdminNotification\Model\InboxFactory $notificationFactory,
        array $data = []
    ) {
        $this->notificationFactory = $notificationFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    public function addMessage(\Ess\M2ePro\Model\Issue\DataObject $issue)
    {
        $dataForAdd = [
            'title'       => $issue->getTitle(),
            'description' => strip_tags($issue->getText()),
            'url'         => $issue->getUrl() !== null ? $issue->getUrl()
                                                       : 'https://m2epro.com/?' . sha1($issue->getTitle()),
            'severity'    => $this->recognizeSeverity($issue),
            'date_added'  => date('Y-m-d H:i:s')
        ];

        $this->notificationFactory->create()->parse([$dataForAdd]);
    }

    //########################################

    protected function recognizeSeverity(\Ess\M2ePro\Model\Issue\DataObject $issue)
    {
        $notice = [
            Message::TYPE_NOTICE,
            Message::TYPE_SUCCESS,
            \Ess\M2ePro\Helper\Module::MESSAGE_TYPE_NOTICE,
            \Ess\M2ePro\Helper\Module::MESSAGE_TYPE_SUCCESS
        ];

        if (in_array($issue->getType(), $notice, true)) {
            return AdminNotification::SEVERITY_NOTICE;
        }

        $warning = [
            Message::TYPE_WARNING,
            \Ess\M2ePro\Helper\Module::MESSAGE_TYPE_WARNING
        ];

        if (in_array($issue->getType(), $warning, true)) {
            return AdminNotification::SEVERITY_MINOR;
        }

        return AdminNotification::SEVERITY_CRITICAL;
    }

    //########################################
}
