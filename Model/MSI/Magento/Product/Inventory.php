<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\MSI\Magento\Product;

use Ess\M2ePro\Model\Magento\Product\Inventory\AbstractModel;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * Class \Ess\M2ePro\Model\MSI\Magento\Product\Inventory
 */
class Inventory extends AbstractModel
{
    /** @var GetStockItemDataInterface */
    private $getStockItemData;
    /** @var GetProductSalableQtyInterface */
    private $salableQtyResolver;
    /** @var StockResolverInterface */
    private $stockResolver;

    //########################################

    /**
     * Inventory constructor.
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    )
    {
        $this->getStockItemData = $objectManager->get(GetStockItemDataInterface::class);
        $this->salableQtyResolver = $objectManager->get(GetProductSalableQtyInterface::class);
        $this->stockResolver = $objectManager->get(StockResolverInterface::class);
        parent::__construct($stockRegistry, $helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @return int|mixed
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isInStock()
    {
        $stockItemData = $this->getStockItemData->execute(
            $this->getProduct()->getSku(), $this->getStock()->getStockId()
        );
        return $stockItemData === null ? 0 : $stockItemData[GetStockItemDataInterface::IS_SALABLE];
    }

    /**
     * @return float|int|mixed
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQty()
    {
        try {
            $qty = $this->salableQtyResolver->execute($this->getProduct()->getSku(), $this->getStock()->getId());
        } catch (\Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException $exception) {
            $qty = 0;
        }

        return $qty;
    }

    /**
     * @return \Magento\InventoryApi\Api\Data\StockInterface
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStock()
    {
        $website = $this->getProduct()->getStoreId() === 0 ?
            $this->getHelper('Magento\Store')->getDefaultWebsite() :
            $this->getProduct()->getStore()->getWebsite();

        return $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
    }

    //########################################
}