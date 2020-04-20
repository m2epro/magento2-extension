<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\InventoryCatalog\Model\ResourceModel;

/**
 * Class \Ess\M2ePro\Plugin\MSI\Magento\InventoryCatalog\Model\ResourceModel\SetDataToLegacyStockItem
 */
class SetDataToLegacyStockItem extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    const PRODUCTS_FOR_REINDEX_REGISTRY_KEY = 'msi_products_for_reindex';

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;
    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    private $productResource;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Magento\Catalog\Model\ResourceModel\Product $productResource
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->globalData = $globalData;
        $this->productResource = $productResource;
    }

    //########################################

    /**
     * @param $interceptor
     * @param \Closure $callback
     * @param mixed ...$arguments
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processExecute($interceptor, \Closure $callback, array $arguments)
    {
        $result = $callback(...$arguments);
        if (!isset($arguments[0])) {
            return $result;
        }

        $productIds = (array)$this->globalData->getValue(self::PRODUCTS_FOR_REINDEX_REGISTRY_KEY);
        $productIds[] = (int)$this->productResource->getIdBySku($arguments[0]);

        $this->globalData->unsetValue(self::PRODUCTS_FOR_REINDEX_REGISTRY_KEY);
        $this->globalData->setValue(self::PRODUCTS_FOR_REINDEX_REGISTRY_KEY, $productIds);

        return $result;
    }

    //########################################
}
