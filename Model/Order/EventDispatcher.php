<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Order;

class EventDispatcher
{
    private const REGION_AMERICA = 'america';
    private const REGION_EUROPE = 'europe';
    private const REGION_ASIA_PACIFIC = 'asia-pacific';

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
            'channel_purchase_date' => $this->findPurchaseDate($order),
            'region' => $this->resolveRegion($order->getMarketplace()),

            // Deprecated. Region flags are necessary to maintain backward compatibility
            'is_american_region' => $marketplace->isAmericanRegion(),
            'is_european_region' => $marketplace->isEuropeanRegion(),
            'is_asian_pacific_region' => $marketplace->isAsianPacificRegion(),
        ]);
    }

    private function findPurchaseDate(\Ess\M2ePro\Model\Order $order): ?\DateTime
    {
        if ($order->isComponentModeEbay()) {
            return \Ess\M2ePro\Helper\Date::createDateGmt(
                $order->getChildObject()->getPurchaseCreateDate()
            );
        }

        return null;
    }

    private function resolveRegion(\Ess\M2ePro\Model\Marketplace $marketplace): string
    {
        if ($marketplace->isAmericanRegion()) {
            return self::REGION_AMERICA;
        }

        if ($marketplace->isEuropeanRegion()) {
            return self::REGION_EUROPE;
        }

        return self::REGION_ASIA_PACIFIC;
    }

    public function dispatchEventInvoiceCreated(\Ess\M2ePro\Model\Order $order): void
    {
        $this->eventManager->dispatch('ess_order_invoice_created', [
            'channel' => $order->getComponentMode(),
            'channel_order_id' => (int)$order->getId(),
        ]);
    }
}
