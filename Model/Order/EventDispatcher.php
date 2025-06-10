<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Order;

class EventDispatcher
{
    private const CHANNEL_EBAY = 'ebay';
    private const CHANNEL_WALMART = 'walmart';

    private const REGION_AMERICA = 'america';
    private const REGION_EUROPE = 'europe';
    private const REGION_ASIA_PACIFIC = 'asia-pacific';

    private \Magento\Framework\Event\ManagerInterface $eventManager;

    public function __construct(\Magento\Framework\Event\ManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    public function dispatchEventsMagentoOrderCreated(\Ess\M2ePro\Model\Order $order): void
    {
        $this->eventManager->dispatch('ess_order_place_success', ['order' => $order]);

        $marketplace = $order->getMarketplace();
        $this->eventManager->dispatch('ess_magento_order_created', [
            'channel' => $this->getChannel($order),
            'channel_order_id' => (int)$order->getId(),
            'channel_external_order_id' => $this->findChannelExternalOrderId($order),
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

    public function dispatchEventInvoiceCreated(\Ess\M2ePro\Model\Order $order): void
    {
        $this->eventManager->dispatch('ess_order_invoice_created', [
            'channel' => $this->getChannel($order),
            'channel_order_id' => (int)$order->getId(),
        ]);
    }

    // ----------------------------------------

    private function getChannel(\Ess\M2ePro\Model\Order $order): string
    {
        if ($order->isComponentModeEbay()) {
            return self::CHANNEL_EBAY;
        }

        if ($order->isComponentModeWalmart()) {
            return self::CHANNEL_WALMART;
        }

        return '';
    }

    private function findChannelExternalOrderId(\Ess\M2ePro\Model\Order $order): ?string
    {
        if ($order->isComponentModeEbay()) {
            /** @var \Ess\M2ePro\Model\Ebay\Order $ebayOrder */
            $ebayOrder = $order->getChildObject();

            return $ebayOrder->getEbayOrderId();
        }

        if ($order->isComponentModeWalmart()) {
            /** @var \Ess\M2ePro\Model\Walmart\Order $walmartOrder */
            $walmartOrder = $order->getChildObject();

            return $walmartOrder->getWalmartOrderId();
        }

        return null;
    }

    private function findPurchaseDate(\Ess\M2ePro\Model\Order $order): ?\DateTime
    {
        if ($order->isComponentModeEbay()) {
            /** @var \Ess\M2ePro\Model\Ebay\Order $ebayOrder */
            $ebayOrder = $order->getChildObject();

            return \Ess\M2ePro\Helper\Date::createDateGmt(
                $ebayOrder->getPurchaseCreateDate()
            );
        }

        if ($order->isComponentModeWalmart()) {
            /** @var \Ess\M2ePro\Model\Walmart\Order $walmartOrder */
            $walmartOrder = $order->getChildObject();

            return \Ess\M2ePro\Helper\Date::createDateGmt(
                $walmartOrder->getPurchaseCreateDate()
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
}
