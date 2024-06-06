<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Product;

class EventDispatcher
{
    /** @var \Magento\Framework\Event\ManagerInterface */
    private $eventManager;

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    public function dispatchEventFbaProductSourceItemsUpdated(string $merchantId): void
    {
        $this->eventManager->dispatch(
            'ess_amazon_fba_product_source_items_updated',
            [
                'merchant_id' => $merchantId,
            ]
        );
    }

    public function dispatchEventFbaProductDeleted(string $merchantId, string $channelSku): void
    {
        $this->eventManager->dispatch(
            'ess_amazon_fba_product_deleted',
            [
                'merchant_id' => $merchantId,
                'channel_sku' => $channelSku,
            ]
        );
    }
}
