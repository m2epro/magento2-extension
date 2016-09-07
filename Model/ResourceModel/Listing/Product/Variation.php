<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product;

class Variation extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Parent\AbstractModel
{
    private $variationsProductsIds = array();

    protected $magentoProductStatus;
    protected $catalogInventoryConfiguration;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Product\Status $magentoProductStatus,
        \Magento\CatalogInventory\Model\Configuration $catalogInventoryConfiguration,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    )
    {
        $this->magentoProductStatus = $magentoProductStatus;
        $this->catalogInventoryConfiguration = $catalogInventoryConfiguration;
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
            return NULL;
        }

        $statuses = $this->getVariationsStatuses($variationsProductsIds, $storeId);

        return (int)max($statuses) == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
    }

    public function isAllStatusesDisabled($listingProductId, $storeId)
    {
        $variationsProductsIds = $this->getVariationsProductsIds($listingProductId);

        if (count($variationsProductsIds) <= 0) {
            return NULL;
        }

        $statuses = $this->getVariationsStatuses($variationsProductsIds, $storeId);

        return (int)min($statuses) == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
    }

    // ---------------------------------------

    public function isAllHaveStockAvailabilities($listingProductId)
    {
        $variationsProductsIds = $this->getVariationsProductsIds($listingProductId);

        if (count($variationsProductsIds) <= 0) {
            return NULL;
        }

        $stocks = $this->getVariationsStockAvailabilities($variationsProductsIds);

        return (int)min($stocks);
    }

    public function isAllDoNotHaveStockAvailabilities($listingProductId)
    {
        $variationsProductsIds = $this->getVariationsProductsIds($listingProductId);

        if (count($variationsProductsIds) <= 0) {
            return NULL;
        }

        $stocks = $this->getVariationsStockAvailabilities($variationsProductsIds);

        return !(int)max($stocks);
    }

    //########################################

    private function getVariationsProductsIds($listingProductId)
    {
        if (isset($this->variationsProductsIds[$listingProductId])) {
            return $this->variationsProductsIds[$listingProductId];
        }

        $optionTable = $this->activeRecordFactory->getObject('Listing\Product\Variation\Option')->getResource()
            ->getMainTable();

        $select = $this->getConnection()
                        ->select()
                        ->from(
                            array('lpv' => $this->getMainTable()),
                            array('variation_id' => 'id')
                        )
                        ->join(
                            array('lpvo' => $optionTable),
                            '`lpv`.`id` = `lpvo`.`listing_product_variation_id`',
                            array('product_id')
                        )
                        ->where('`lpv`.`listing_product_id` = ?',(int)$listingProductId);

        $result = array();

        foreach ($select->query()->fetchAll() as $value) {
            if (empty($value['product_id'])) {
                continue;
            }

            $result[$value['variation_id']][] = $value['product_id'];
        }

        return $this->variationsProductsIds[$listingProductId] = $result;
    }

    // ---------------------------------------

    private function getVariationsStatuses(array $variationsProductsIds, $storeId)
    {
        $productsIds = array();

        foreach ($variationsProductsIds as $variationProductsIds) {
            foreach ($variationProductsIds as $variationProductId) {
                $productsIds[] = $variationProductId;
            }
        }

        $productsIds = array_values(array_unique($productsIds));
        $statuses = $this->magentoProductStatus->getProductStatus($productsIds, $storeId);

        $variationsProductsStatuses = array();
        foreach ($variationsProductsIds as $key => $variationProductsIds) {
            foreach ($variationProductsIds as $variationProductId) {
                $variationsProductsStatuses[$key][] = $statuses[$variationProductId];
            }
        }

        $variationsStatuses = array();
        foreach ($variationsProductsStatuses as $key => $variationProductsStatuses) {
            $variationsStatuses[$key] = max($variationProductsStatuses);
        }

        return $variationsStatuses;
    }

    private function getVariationsStockAvailabilities(array $variationsProductsIds)
    {
        $productsIds = array();

        foreach ($variationsProductsIds as $variationProductsIds) {
            foreach ($variationProductsIds as $variationProductId) {
                $productsIds[] = $variationProductId;
            }
        }

        $productsIds = array_values(array_unique($productsIds));
        $catalogInventoryTable = $this->getTable('cataloginventory_stock_item');

        $select = $this->getConnection()
                       ->select()
                       ->from(
                            array('cisi' => $catalogInventoryTable),
                            array('product_id','is_in_stock', 'manage_stock', 'use_config_manage_stock')
                       )
                       ->where('cisi.product_id IN ('.implode(',',$productsIds).')');

        $stocks = $select->query()->fetchAll();

        $variationsProductsStocks = array();
        foreach ($variationsProductsIds as $key => $variationProductsIds) {
            foreach ($variationProductsIds as $id) {
                $count = count($stocks);
                for ($i = 0; $i < $count; $i++) {
                    if ($stocks[$i]['product_id'] == $id) {
                        $stockAvailability = $this->getHelper('Magento\Product')->calculateStockAvailability(
                            $stocks[$i]['is_in_stock'],
                            $stocks[$i]['manage_stock'],
                            $stocks[$i]['use_config_manage_stock']
                        );
                        $variationsProductsStocks[$key][] = $stockAvailability;
                        break;
                    }
                }
            }
        }

        $variationsStocks = array();
        foreach ($variationsProductsStocks as $key => $variationProductsStocks) {
            $variationsStocks[$key] = min($variationProductsStocks);
        }

        return $variationsStocks;
    }

    //########################################
}