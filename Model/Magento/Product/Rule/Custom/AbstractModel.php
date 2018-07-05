<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\Rule\Custom;

abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface  */
    protected $localeDate;

    /** @var \Magento\CatalogInventory\Api\StockRegistryInterface  */
    protected $stockRegistry;

    /** @var \Magento\CatalogInventory\Api\StockConfigurationInterface  */
    protected $stockConfiguration;

    protected $filterOperator  = NULL;
    protected $filterCondition = NULL;

    //########################################

    public function __construct(
        $filterOperator, $filterCondition,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    )
    {
        $this->localeDate          = $localeDate;
        $this->stockRegistry       = $stockRegistry;
        $this->stockConfiguration  = $stockConfiguration;

        $this->filterOperator  = $filterOperator;
        $this->filterCondition = $filterCondition;

        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    abstract public function getAttributeCode();

    abstract public function getLabel();

    abstract public function getValueByProductInstance(\Magento\Catalog\Model\Product $product);

    //########################################

    /**
     * @return string
     */
    public function getInputType()
    {
        return 'string';
    }

    /**
     * @return string
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return array();
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface
     */
    protected function getStockItemByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        return $this->stockRegistry
                    ->getStockItem(
                        $product->getId(),
                        $product->getStore()->getWebsiteId()
                    );
    }

    //########################################
}