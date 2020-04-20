<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Amazon;

use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Ess\M2ePro\Model\MSI\Order\Reserve as MSIReserve;

/**
 * Class \Ess\M2ePro\Observer\Amazon\Order
 */
class Order extends \Ess\M2ePro\Observer\AbstractModel
{
    /** @var \Magento\CatalogInventory\Api\StockRegistryInterface  */
    protected $stockRegistry;

    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    //########################################

    public function __construct(
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->objectManager = $objectManager;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        /** @var $magentoOrder \Magento\Sales\Model\Order */
        $magentoOrder = $this->getEvent()->getMagentoOrder();

        $itemsToSell = [];
        foreach ($magentoOrder->getAllItems() as $orderItem) {
            /** @var $orderItem \Magento\Sales\Model\Order\Item */

            if ($orderItem->getHasChildren()) {
                continue;
            }

            $itemsToSell[] = [
                'id'      => $orderItem->getProductId(),
                'sku'     => $orderItem->getSku(),
                'qty'     => (float)$orderItem->getQtyOrdered(),
                'website' => $orderItem->getStore()->getWebsiteId()
            ];
        }

        $this->placeReservation($magentoOrder, $itemsToSell);
    }

    private function placeReservation(\Magento\Sales\Model\Order $magentoOrder, array $itemsToSell)
    {
        if ($this->helperFactory->getObject('Magento')->isMSISupportingVersion()) {

            $reservation = $this->objectManager->get(MSIReserve::class);
            $reservation->placeCompensationReservation(
                $itemsToSell,
                $magentoOrder->getStoreId(),
                [
                    'type'       => MSIReserve::EVENT_TYPE_COMPENSATING_RESERVATION_FBA_CREATED,
                    'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
                    'objectId'   => (string)$magentoOrder->getId()
                ]
            );

            return;
        }

        foreach ($itemsToSell as $itemToSell) {

            $stockItem = $this->stockRegistry->getStockItem($itemToSell['id'], $itemToSell['website']);

            /** @var \Ess\M2ePro\Model\Magento\Product\StockItem $magentoStockItem */
            $magentoStockItem = $this->modelFactory->getObject('Magento_Product_StockItem', [
                'stockItem' => $stockItem
            ]);
            $magentoStockItem->addQty($itemToSell['qty']);
        }
    }

    //########################################
}
