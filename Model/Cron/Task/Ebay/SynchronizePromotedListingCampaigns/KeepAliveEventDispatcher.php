<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Ebay\SynchronizePromotedListingCampaigns;

class KeepAliveEventDispatcher
{
    private \Magento\Framework\Event\ManagerInterface $eventDispatcher;

    public function __construct(\Magento\Framework\Event\ManagerInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function process(int $currentItemPage)
    {
        $this->eventDispatcher->dispatch(
            \Ess\M2ePro\Model\Cron\Strategy\AbstractModel::PROGRESS_SET_DETAILS_EVENT_NAME,
            [
                'progress_nick' => \Ess\M2ePro\Model\Cron\Task\Ebay\SynchronizePromotedListingCampaigns::NICK,
                'total' => $currentItemPage,
            ]
        );
    }
}
