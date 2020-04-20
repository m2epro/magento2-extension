<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\StockItem;

/**
 * Class \Ess\M2ePro\Observer\StockItem\AbstractStockItem
 */
abstract class AbstractStockItem extends \Ess\M2ePro\Observer\AbstractModel
{
    protected $registry;
    protected $stockItemFactory;
    /**
     * @var null|\Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    protected $stockItem = null;

    /**
     * @var null|int
     */
    protected $stockItemId = null;
    /**
     * @var null|int
     */
    protected $storeId = null;

    //########################################

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory $stockItemFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->registry = $registry;
        $this->stockItemFactory = $stockItemFactory;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function beforeProcess()
    {
        $stockItem = $this->getEventObserver()->getData('item');

        if (!($stockItem instanceof \Magento\CatalogInventory\Api\Data\StockItemInterface)) {
            throw new \Ess\M2ePro\Model\Exception('StockItem event doesn\'t have correct StockItem instance.');
        }

        $this->stockItem = $stockItem;

        $this->stockItemId = (int)$this->stockItem->getId();
        $this->storeId = (int)$this->stockItem->getData('store_id');
    }

    //########################################

    /**
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getStockItem()
    {
        if (!($this->stockItem instanceof \Magento\CatalogInventory\Api\Data\StockItemInterface)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Property "StockItem" should be set first.');
        }

        return $this->stockItem;
    }

    /**
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
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
