<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Amazon;

class Order extends \Ess\M2ePro\Observer\AbstractModel
{
    protected $itemFactory;

    //########################################

    public function __construct(
        \Magento\CatalogInventory\Model\Stock\ItemFactory $itemFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->itemFactory = $itemFactory;
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

            /** @var $stockItem \Magento\CatalogInventory\Model\Stock\Item */
            $stockItem = $this->itemFactory->create();
            $stockItem->getResource()->loadByProductId(
                $stockItem, $orderItem->getProductId(), $stockItem->getStockId()
            );

            if (!$stockItem->getId()) {
                continue;
            }

            $magentoStockItem = $this->modelFactory->getObject('Magento\Product\StockItem');
            $magentoStockItem->setStockItem($stockItem);
            $magentoStockItem->addQty($orderItem->getQtyOrdered());
        }
    }

    //########################################
}