<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation\StockDataResolver
 */
class StockDataResolver extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        array $data = []
    ) {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

   public function resolve(array $variationsProductsIds, $storeId)
   {
       $productsIds = [];
       foreach ($variationsProductsIds as $variationProductsIds) {
           foreach ($variationProductsIds as $variationProductId) {
               $productsIds[] = $variationProductId;
           }
       }
       $productsIds = array_values(array_unique($productsIds));

       $select = $this->resourceConnection->getConnection()
           ->select()
           ->from(
               [
                   'cisi' => $this->getHelper('Module_Database_Structure')
                       ->getTableNameWithPrefix('cataloginventory_stock_item')
               ],
               ['product_id', 'manage_stock', 'use_config_manage_stock', 'is_in_stock']
           )
           ->where('cisi.product_id IN ('.implode(',', $productsIds).')')
           ->where('cisi.stock_id = ?', $this->helperFactory->getObject('Magento\Stock')->getStockId($storeId))
           ->where('cisi.website_id = ?', $this->helperFactory->getObject('Magento\Stock')->getWebsiteId($storeId));

       return $select->query()->fetchAll();
   }

    //########################################
}
