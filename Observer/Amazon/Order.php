<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Amazon;

class Order extends \Ess\M2ePro\Observer\AbstractModel
{
    /** @var \Magento\CatalogInventory\Api\StockRegistryInterface  */
    protected $stockRegistry;

    //########################################

    public function __construct(
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->stockRegistry    = $stockRegistry;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        /** @var $magentoOrder \Magento\Sales\Model\Order */
        $magentoOrder = $this->getEvent()->getMagentoOrder();

        foreach ($magentoOrder->getAllItems() as $orderItem) {

            /** @var $orderItem \Magento\Sales\Model\Order\Item */

            if ($orderItem->getHasChildren()) {
                continue;
            }

            $stockItem = $this->stockRegistry->getStockItem(
                $orderItem->getProductId(),
                $orderItem->getStore()->getWebsiteId()
            );

            /** @var \Ess\M2ePro\Model\Magento\Product\StockItem $magentoStockItem */
            $magentoStockItem = $this->modelFactory->getObject('Magento\Product\StockItem', [
                'stockItem' => $stockItem
            ]);
            $magentoStockItem->addQty($orderItem->getQtyOrdered());
        }
    }

    //########################################
}