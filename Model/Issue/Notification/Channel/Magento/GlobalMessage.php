<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Issue\Notification\Channel\Magento;

use Ess\M2ePro\Helper\Date;
use Ess\M2ePro\Model\Issue\DataObject;
use Ess\M2ePro\Model\Issue\Notification\ChannelInterface;
use Magento\AdminNotification\Model\InboxFactory;
use Magento\Framework\Notification\MessageInterface as AdminNotification;
use Magento\Framework\Message\MessageInterface as Message;

class GlobalMessage implements ChannelInterface
{
    /** @var InboxFactory */
    private $notificationFactory;

    /**
     * @param \Magento\AdminNotification\Model\InboxFactory $notificationFactory
     */
    public function __construct(InboxFactory $notificationFactory)
    {
        $this->notificationFactory = $notificationFactory;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function addMessage(DataObject $message): void
    {
        $url = $message->getUrl() ?? 'https://m2epro.com/?' . sha1($message->getTitle());
        $dataForAdd = [
            'title' => $message->getTitle(),
            'description' => strip_tags($message->getText()),
            'url' => $url,
            'severity' => $this->recognizeSeverity($message),
            'date_added' => Date::createCurrentGmt()->format('Y-m-d H:i:s'),
        ];

        $this->notificationFactory->create()->parse([$dataForAdd]);
    }

    /**
     * @param DataObject $issue
     *
     * @return int
     */
    private function recognizeSeverity(DataObject $issue): int
    {
        $notice = [
            Message::TYPE_NOTICE,
            Message::TYPE_SUCCESS,
            \Ess\M2ePro\Helper\Module::MESSAGE_TYPE_NOTICE,
            \Ess\M2ePro\Helper\Module::MESSAGE_TYPE_SUCCESS,
        ];

        if (in_array($issue->getType(), $notice, true)) {
            return AdminNotification::SEVERITY_NOTICE;
        }

        $warning = [
            Message::TYPE_WARNING,
            \Ess\M2ePro\Helper\Module::MESSAGE_TYPE_WARNING,
        ];

        if (in_array($issue->getType(), $warning, true)) {
            return AdminNotification::SEVERITY_MINOR;
        }

        return AdminNotification::SEVERITY_CRITICAL;
    }
}
