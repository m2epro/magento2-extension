<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\InventoryShipping\Observer;

use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\InventorySourceDeductionApi\Model\GetSourceItemBySourceCodeAndSku;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Ess\M2ePro\Model\MSI\Order\Reserve as MSIReserve;

/**
 * Class \Ess\M2ePro\Plugin\MSI\Magento\InventoryShipping\Observer\SourceDeductionProcessor
 */
class SourceDeductionProcessor extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;

    // ---------------------------------------

    /** @var StockResolverInterface $stockResolver */
    private $stockResolver;

    /** @var \Magento\Sales\Model\Order\Shipment */
    private $shipment;

    /** @var IsSingleSourceModeInterface */
    private $isSingleSourceMode;

    /** @var DefaultSourceProviderInterface */
    private $defaultSourceProvider;

    /** @var GetStockItemConfigurationInterface */
    private $getStockItemConfiguration;

    /** @var GetSourceItemBySourceCodeAndSku */
    private $getSourceItemBySourceCodeAndSku;

    /** @var SourceItemsSaveInterface */
    private $sourceItemsSave;

    /** @var \Ess\M2ePro\Model\MSI\Order\Reserve */
    private $msiReservation;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->activeRecordFactory = $activeRecordFactory;

        $this->stockResolver = $objectManager->get(StockResolverInterface::class);
        $this->isSingleSourceMode = $objectManager->get(IsSingleSourceModeInterface::class);
        $this->defaultSourceProvider = $objectManager->get(DefaultSourceProviderInterface::class);
        $this->getStockItemConfiguration = $objectManager->get(GetStockItemConfigurationInterface::class);
        $this->getSourceItemBySourceCodeAndSku = $objectManager->get(GetSourceItemBySourceCodeAndSku::class);
        $this->sourceItemsSave = $objectManager->get(SourceItemsSaveInterface::class);
        $this->msiReservation = $objectManager->get(MSIReserve::class);
}

    //########################################

    /**
     * @param $interceptor
     * @param \Closure $callback
     * @param array ...$arguments
     * @return mixed
     */
    public function aroundExecute($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('execute', $interceptor, $callback, $arguments);
    }

    /**
     * @param $interceptor
     * @param \Closure $callback
     * @param array $arguments
     * @return mixed
     */
    public function processExecute($interceptor, \Closure $callback, array $arguments)
    {
        $result = $callback(...$arguments);
        if (!isset($arguments[0]) || !($arguments[0] instanceof \Magento\Framework\Event\Observer)) {
            return $result;
        }
        $this->shipment = $arguments[0]->getEvent()->getShipment();

        if (!$this->isNeedToProcess()) {
            return $result;
        }

        if (!empty($this->shipment->getExtensionAttributes()) &&
            !empty($this->shipment->getExtensionAttributes()->getSourceCode())) {
            $sourceCode = $this->shipment->getExtensionAttributes()->getSourceCode();
        } elseif ($this->isSingleSourceMode->execute()) {
            $sourceCode = $this->defaultSourceProvider->getCode();
        }

        $website = $this->shipment->getOrder()->getStore()->getWebsite();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());

        $sourceItems = [];
        $reservationItems = [];

        foreach ($this->shipment->getAllItems() as $item) {
            $stockItemConfiguration = $this->getStockItemConfiguration->execute(
                $item->getSku(),
                $stock->getStockId()
            );

            if (!$stockItemConfiguration->isManageStock()) {
                continue;
            }

            $sourceItem = $this->getSourceItemBySourceCodeAndSku->execute($sourceCode, $item->getSku());
            $sourceItem->setQuantity($sourceItem->getQuantity() + $item->getQty());
            $sourceItems[] = $sourceItem;

            $reservationItems[] = [
                'sku' => $item->getSku(),
                'qty' => -(float)$item->getQty(),
            ];
        }

        if (!empty($sourceItems)) {
            $this->sourceItemsSave->execute($sourceItems);
        }

        $this->msiReservation->placeCompensationReservation(
            $reservationItems,
            $this->shipment->getOrder()->getStoreId(),
            [
                'type'       => MSIReserve::EVENT_TYPE_COMPENSATING_RESERVATION_FBA_SHIPPED,
                'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
                'objectId'   => (string)$this->shipment->getOrder()->getId()
            ]
        );

        return $result;
    }

    private function isNeedToProcess()
    {
        $magentoOrderId = $this->shipment->getOrderId();

        try {
            /** @var $order \Ess\M2ePro\Model\Order */
            $order = $this->activeRecordFactory->getObjectLoaded('Order', $magentoOrderId, 'magento_order_id');
        } catch (\Exception $e) {
            return false;
        }

        if (!$order->isComponentModeAmazon() || !$order->getChildObject()->isFulfilledByAmazon()) {
            return false;
        }

        if ($order->getChildObject()->getAmazonAccount()->isMagentoOrdersFbaStockEnabled()) {
            return false;
        }

        if ($this->shipment->getOrigData('entity_id')) {
            return false;
        }

        return true;
    }

    //########################################
}
