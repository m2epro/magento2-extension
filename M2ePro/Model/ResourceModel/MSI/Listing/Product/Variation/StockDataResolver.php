<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\MSI\Listing\Product\Variation;

use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\MSI\Listing\Product\Variation\StockDataResolver
 */
class StockDataResolver extends \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation\StockDataResolver
{
    /** @var StockIndexTableNameResolverInterface */
    protected $indexNameResolver;

    /** @var StockByWebsiteIdResolverInterface */
    protected $stockResolver;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        $this->indexNameResolver = $objectManager->get(StockIndexTableNameResolverInterface::class);
        $this->stockResolver = $objectManager->get(StockByWebsiteIdResolverInterface::class);

        parent::__construct($helperFactory, $modelFactory, $resourceConnection, $data);
    }

    //########################################

    public function resolve(array $variationsProductsIds, $storeId)
    {
        $stockItems = parent::resolve($variationsProductsIds, $storeId);

        $website = $storeId === \Magento\Store\Model\Store::DEFAULT_STORE_ID
            ? $this->helperFactory->getObject('Magento\Store')->getDefaultWebsite()
            : $this->helperFactory->getObject('Magento\Store')->getWebsite($storeId);

        $stockId = $this->stockResolver->execute($website->getId())->getStockId();

        $salableData = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                ['it' => $this->indexNameResolver->execute($stockId)],
                'is_salable'
            )
            ->join(
                [
                    'cpe' => $this->getHelper('Module_Database_Structure')
                                        ->getTableNameWithPrefix('catalog_product_entity')
                ],
                'cpe.sku = it.sku',
                ['product_id' => 'entity_id']
            )
            ->where('cpe.entity_id IN (?)', array_column($stockItems, 'product_id'))
            ->query()
            ->fetchAll();

        foreach ($stockItems as &$stockItem) {
            foreach ($salableData as $row) {
                if ($row['product_id'] == $stockItem['product_id']) {
                    $stockItem['is_in_stock'] = $row['is_salable'];
                    continue 2;
                }
            }

            $stockItem['is_in_stock'] = 0;
        }
        unset($stockItem);

        return $stockItems;
    }

    //########################################
}
