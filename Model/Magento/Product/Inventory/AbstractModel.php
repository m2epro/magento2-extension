<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\Inventory;

use Ess\M2ePro\Model\Exception;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Inventory\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\CatalogInventory\Api\StockRegistryInterface */
    private $stockRegistry;
    /** @var \Magento\Catalog\Model\Product */
    private $product;

    //########################################

    /**
     * AbstractModel constructor.
     *
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param array $data
     */
    public function __construct(
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->stockRegistry = $stockRegistry;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return $this
     */
    public function setProduct(\Magento\Catalog\Model\Product $product)
    {
        $this->product = $product;
        return $this;
    }

    /**
     * @return \Magento\Catalog\Model\Product
     * @throws Exception
     */
    public function getProduct()
    {
        if ($this->product === null) {
            throw new Exception('Catalog Product Model is not set');
        }

        return $this->product;
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function isStockAvailability()
    {
        return $this->getHelper('Magento\Product')->calculateStockAvailability(
            $this->isInStock(),
            $this->getStockItem()->getManageStock(),
            $this->getStockItem()->getUseConfigManageStock()
        );
    }

    /**
     * @return mixed
     */
    abstract public function isInStock();

    /**
     * @return mixed
     */
    abstract public function getQty();

    //########################################

    /**
     * @param bool $withScope
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
     * @throws Exception
     */
    public function getStockItem($withScope = true)
    {
        return $this->stockRegistry->getStockItem(
            $this->getProduct()->getId(),
            $withScope ? $this->getProduct()->getStore()->getWebsiteId() : null
        );
    }

    //########################################
}