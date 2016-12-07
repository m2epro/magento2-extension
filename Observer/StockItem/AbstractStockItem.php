<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\StockItem;

abstract class AbstractStockItem extends \Ess\M2ePro\Observer\AbstractModel
{
    protected $registry;
    protected $stockItemFactory;
    /**
     * @var null|\Magento\CatalogInventory\Model\Stock\Item
     */
    protected $stockItem = NULL;

    /**
     * @var null|int
     */
    protected $stockItemId = NULL;
    /**
     * @var null|int
     */
    protected $storeId = NULL;

    //########################################

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\CatalogInventory\Model\Stock\ItemFactory $stockItemFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->registry = $registry;
        $this->stockItemFactory = $stockItemFactory;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function beforeProcess()
    {
        $stockItem = $this->getEventObserver()->getData('object');

        if (!($stockItem instanceof \Magento\CatalogInventory\Model\Stock\Item)) {
            throw new \Ess\M2ePro\Model\Exception('StockItem event doesn\'t have correct StockItem instance.');
        }

        $this->stockItem = $stockItem;

        $this->stockItemId = (int)$this->stockItem->getId();
        $this->storeId = (int)$this->stockItem->getData('store_id');
    }

    //########################################

    /**
     * @return \Magento\CatalogInventory\Model\Stock\Item
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getStockItem()
    {
        if (!($this->stockItem instanceof \Magento\CatalogInventory\Model\Stock\Item)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Property "StockItem" should be set first.');
        }

        return $this->stockItem;
    }

    /**
     * @return \Magento\CatalogInventory\Model\Stock\Item
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function reloadStockItem()
    {
        if ($this->getStockItemId() <= 0) {
            throw new \Ess\M2ePro\Model\Exception\Logic('To reload StockItem instance stockitem_id should be
                greater than 0.');
        }

        $this->stockItem = $this->stockItemFactory->create()
                                              ->setStoreId($this->getStoreId())
                                              ->load($this->getStockItemId());

        return $this->getStockItem();
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getStockItemId()
    {
        return (int)$this->stockItemId;
    }

    /**
     * @return int
     */
    protected function getStoreId()
    {
        return (int)$this->storeId;
    }

    //########################################

    /**
     * @return \Magento\Framework\Registry
     */
    protected function getRegistry()
    {
        return $this->registry;
    }

    //########################################
}