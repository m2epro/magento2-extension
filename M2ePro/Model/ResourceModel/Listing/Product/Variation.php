<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product;

use Ess\M2ePro\Model\ResourceModel\MSI\Listing\Product\Variation\StockDataResolver as MSIStockDataResolver;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation
 */
class Variation extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Parent\AbstractModel
{
    protected $variationsProductsIds = [];

    protected $magentoProductStatus;
    protected $catalogInventoryConfiguration;
    protected $variationStockDataResolver;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Product\Status $magentoProductStatus,
        \Magento\CatalogInventory\Model\Configuration $catalogInventoryConfiguration,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $connectionName = null
    ) {
        $this->magentoProductStatus = $magentoProductStatus;
        $this->catalogInventoryConfiguration = $catalogInventoryConfiguration;

        $this->variationStockDataResolver = $helperFactory->getObject('Magento')->isMSISupportingVersion()
            ? $objectManager->get(MSIStockDataResolver::class)
            : $objectManager->get(Variation\StockDataResolver::class);

        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_listing_product_variation', 'id');
    }

    //########################################

    public function isAllStatusesEnabled($listingProductId, $storeId)
    {
        $variationsProductsIds = $this->getVariationsProductsIds($listingProductId);

        if (count($variationsProductsIds) <= 0) {
            return null;
        }

        $statuses = $this->getVariationsStatuses($variationsProductsIds, $storeId);

        if (empty($statuses)) {
            return null;
        }

        return (int)max($statuses) == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
    }

    public function isAllStatusesDisabled($listingProductId, $storeId)
    {
        $variationsProductsIds = $this->getVariationsProductsIds($listingProductId);

        if (count($variationsProductsIds) <= 0) {
            return null;
        }

        $statuses = $this->getVariationsStatuses($variationsProductsIds, $storeId);

        if (empty($statuses)) {
            return null;
        }

        return (int)min($statuses) == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
    }

    // ---------------------------------------

    public function isAllHaveStockAvailabilities($listingProductId, $storeId)
    {
        $variationsProductsIds = $this->getVariationsProductsIds($listingProductId);

        if (count($variationsProductsIds) <= 0) {
            return null;
        }

        $stocks = $this->getVariationsStockAvailabilities($variationsProductsIds, $storeId);

        if (empty($stocks)) {
            return null;
        }

        return (int)min($stocks);
    }

    public function isAllDoNotHaveStockAvailabilities($listingProductId, $storeId)
    {
        $variationsProductsIds = $this->getVariationsProductsIds($listingProductId);

        if (count($variationsProductsIds) <= 0) {
            return null;
        }

        $stocks = $this->getVariationsStockAvailabilities($variationsProductsIds, $storeId);

        if (empty($stocks)) {
            return null;
        }

        return !(int)max($stocks);
    }

    //########################################

    private function getVariationsProductsIds($listingProductId)
    {
        if (isset($this->variationsProductsIds[$listingProductId])) {
            return $this->variationsProductsIds[$listingProductId];
        }

        $optionTable = $this->activeRecordFactory->getObject('Listing_Product_Variation_Option')->getResource()
            ->getMainTable();

        $select = $this->getConnection()
                        ->select()
                        ->from(
                            ['lpv' => $this->getMainTable()],
                            ['variation_id' => 'id']
                        )
                        ->join(
                            ['lpvo' => $optionTable],
                            '`lpv`.`id` = `lpvo`.`listing_product_variation_id`',
                            ['product_id']
                        )
                        ->where('`lpv`.`listing_product_id` = ?', (int)$listingProductId);

        $result = [];

        foreach ($select->query()->fetchAll() as $value) {
            if (empty($value['product_id'])) {
                continue;
            }

            $result[$value['variation_id']][] = $value['product_id'];
        }

        return $this->variationsProductsIds[$listingProductId] = $result;
    }

    // ---------------------------------------

    protected function getVariationsStatuses(array $variationsProductsIds, $storeId)
    {
        $productsIds = [];

        foreach ($variationsProductsIds as $variationProductsIds) {
            foreach ($variationProductsIds as $variationProductId) {
                $productsIds[] = $variationProductId;
            }
        }

        $productsIds = array_values(array_unique($productsIds));
        $statuses = $this->magentoProductStatus->getProductStatus($productsIds, $storeId);

        $variationsProductsStatuses = [];
        foreach ($variationsProductsIds as $key => $variationProductsIds) {
            foreach ($variationProductsIds as $variationProductId) {
                $variationsProductsStatuses[$key][] = $statuses[$variationProductId];
            }
        }

        $variationsStatuses = [];
        foreach ($variationsProductsStatuses as $key => $variationProductsStatuses) {
            $variationsStatuses[$key] = max($variationProductsStatuses);
        }

        return $variationsStatuses;
    }

    protected function getVariationsStockAvailabilities(array $variationsProductsIds, $storeId)
    {
        $stockItems = $this->variationStockDataResolver->resolve($variationsProductsIds, $storeId);
        $stockItemsCount = count($stockItems);

        $variationsProductsStocks = [];
        foreach ($variationsProductsIds as $key => $variationProductsIds) {
            foreach ($variationProductsIds as $id) {

                for ($i = 0; $i < $stockItemsCount; $i++) {
                    if ($stockItems[$i]['product_id'] == $id) {
                        $stockAvailability = $this->getHelper('Magento\Product')->calculateStockAvailability(
                            $stockItems[$i]['is_in_stock'],
                            $stockItems[$i]['manage_stock'],
                            $stockItems[$i]['use_config_manage_stock']
                        );
                        $variationsProductsStocks[$key][] = $stockAvailability;
                        break;
                    }
                }
            }
        }

        $variationsStocks = [];
        foreach ($variationsProductsStocks as $key => $variationProductsStocks) {
            $variationsStocks[$key] = min($variationProductsStocks);
        }

        return $variationsStocks;
    }

    //########################################
}
