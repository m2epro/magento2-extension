<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Order;

class EventDispatcher
{
    /** @var \Magento\Framework\Event\ManagerInterface */
    private $eventManager;

    public function __construct(\Magento\Framework\Event\ManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    public function dispatchEventsMagentoOrderCreated(\Ess\M2ePro\Model\Order $order): void
    {
        $this->eventManager->dispatch('ess_order_place_success', ['order' => $order]);

        $marketplace = $order->getMarketplace();
        $this->eventManager->dispatch('ess_magento_order_created', [
            'channel' => $order->getComponentMode(),
            'channel_order_id' => (int)$order->getId(),
            'magento_order_id' => (int)$order->getMagentoOrderId(),
            'magento_order_increment_id' => $order->getMagentoOrder()->getIncrementId(),
            'is_american_region' => $marketplace->isAmericanRegion(),
            'is_european_region' => $marketplace->isEuropeanRegion(),
            'is_asian_pacific_region' => $marketplace->isAsianPacificRegion(),
        ]);
    }

    public function dispatchEventInvoiceCreated(\Ess\M2ePro\Model\Order $order): void
    {
        $this->eventManager->dispatch('ess_order_invoice_created', [
            'channel' => $order->getComponentMode(),
            'channel_order_id' => (int)$order->getId(),
        ]);
    }
}
