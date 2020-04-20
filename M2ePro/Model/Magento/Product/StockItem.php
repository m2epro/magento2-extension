<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\StockItem
 */
class StockItem extends \Ess\M2ePro\Model\AbstractModel
{
    private $stockConfiguration;

    /** @var \Magento\CatalogInventory\Api\Data\StockItemInterface|null  */
    private $stockItem = null;

    /** @var \Magento\CatalogInventory\Model\Indexer\Stock\Processor */
    private $indexStockProcessor = null;

    /** @var \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface */
    private $stockStateProvider;

    /** @var bool */
    private $stockStatusChanged = false;

    /** @var \Magento\CatalogInventory\Api\StockItemRepositoryInterface|null  */
    private $stockItemRepository = null;

    /** @var \Magento\CatalogInventory\Model\StockRegistryStorage $stockRegistryStorage */
    private $stockRegistryStorage;

    //########################################

    public function __construct(
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\CatalogInventory\Model\Indexer\Stock\Processor $indexStockProcessor,
        \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface $stockStateProvider,
        \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem,
        \Magento\CatalogInventory\Api\StockItemRepositoryInterface $stockItemRepository,
        \Magento\CatalogInventory\Model\StockRegistryStorage $stockRegistryStorage
    ) {
        $this->stockConfiguration  = $stockConfiguration;
        $this->indexStockProcessor = $indexStockProcessor;
        $this->stockStateProvider  = $stockStateProvider;
        $this->stockItem           = $stockItem;
        $this->stockItemRepository = $stockItemRepository;
        $this->stockRegistryStorage = $stockRegistryStorage;

        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getStockItem()
    {
        if ($this->stockItem === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Stock Item is not set.');
        }

        return $this->stockItem;
    }

    public function subtractQty($qty, $save = true)
    {
        if (!$this->canChangeQty()) {
            return false;
        }

        $stockItem = $this->getStockItem();

        if ($stockItem->getQty() - $stockItem->getMinQty() - $qty < 0) {
            switch ($stockItem->getBackorders()) {
                case \Magento\CatalogInventory\Model\Stock::BACKORDERS_YES_NONOTIFY:
                case \Magento\CatalogInventory\Model\Stock::BACKORDERS_YES_NOTIFY:
                    break;
                default:
                    throw new \Ess\M2ePro\Model\Exception('The requested Quantity is not available.');
            }
        }

        if ($stockItem->getManageStock() && $this->stockConfiguration->canSubtractQty()) {
            $stockItem->setQty($stockItem->getQty() - $qty);
        }

        if (!$this->stockStateProvider->verifyStock($stockItem)) {
            $this->stockStatusChanged = true;
        }

        if ($save) {
            $this->stockItemRepository->save($stockItem);
            $this->afterSave();
        }

        return true;
    }

    /**
     * @param $qty
     * @param bool $save
     * @return bool
     */
    public function addQty($qty, $save = true)
    {
        if (!$this->canChangeQty()) {
            return false;
        }

        $stockItem = $this->getStockItem();
        $stockItem->setQty($stockItem->getQty() + $qty);

        if ($stockItem->getQty() > $stockItem->getMinQty()) {
            $stockItem->setIsInStock(true);
            $this->stockStatusChanged = true;
        }

        if ($save) {
            $this->stockItemRepository->save($stockItem);
            $this->afterSave();
        }

        return true;
    }

    //########################################

    public function afterSave()
    {
        if ($this->indexStockProcessor->isIndexerScheduled()) {
            $this->indexStockProcessor->reindexRow($this->getStockItem()->getProductId(), true);
        }

        if ($this->stockStatusChanged) {
            $this->stockRegistryStorage->removeStockStatus($this->getStockItem()->getProductId());
        }
    }

    //----------------------------------------

    public function isStockStatusChanged()
    {
        return (bool)$this->stockStatusChanged;
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function canChangeQty()
    {
        return $this->getHelper('Magento\Stock')->canSubtractQty() && $this->getStockItem()->getManageStock();
    }

    //########################################
}
