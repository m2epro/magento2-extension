<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\CatalogInventory\Model\Indexer\Stock;

/**
 * Class \Ess\M2ePro\Plugin\MSI\Magento\CatalogInventory\Model\Indexer\Stock\Processor
 */
class Processor extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    const PRODUCTS_FOR_REINDEX_REGISTRY_KEY = 'msi_products_for_reindex';

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalData;
    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    private $productResource;
    /** @var \Magento\Framework\Indexer\IndexerRegistry */
    protected $indexerRegistry;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Data\GlobalData $globalData,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->globalData = $globalData;
        $this->productResource = $productResource;
        $this->indexerRegistry = $indexerRegistry;
    }

    //########################################

    protected function canExecute()
    {
        if (!$this->getHelper('Magento')->isMSISupportingVersion()) {
            return false;
        }

        return parent::canExecute();
    }

    /**
     * @param $interceptor
     * @param \Closure $callback
     * @param mixed ...$arguments
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function aroundReindexList($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('reindexList', $interceptor, $callback, $arguments);
    }

    /**
     * @param $interceptor
     * @param \Closure $callback
     * @param array $arguments
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function processReindexList($interceptor, \Closure $callback, array $arguments)
    {
        $result = $callback(...$arguments);
        if (!isset($arguments[0])) {
            return $result;
        }

        $productIds = (array)$this->globalData->getValue(self::PRODUCTS_FOR_REINDEX_REGISTRY_KEY);
        $this->globalData->unsetValue(self::PRODUCTS_FOR_REINDEX_REGISTRY_KEY);

        if ($productIds !== $arguments[0]) {
            return $result;
        }

        if (isset($arguments[1]) && $arguments[1] === true) {
            return $result;
        }

        $indexer = $this->indexerRegistry->get(\Magento\CatalogInventory\Model\Indexer\Stock\Processor::INDEXER_ID);
        if ($indexer->isScheduled()) {
            $indexer->reindexList($productIds);
        }

        return $result;
    }

    //########################################
}
