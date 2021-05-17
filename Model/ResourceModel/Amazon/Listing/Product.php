<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product
 */
class Product extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    protected $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);

        $this->amazonFactory = $amazonFactory;
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_listing_product', 'listing_product_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function getProductsDataBySkus(
        array $skus = [],
        array $filters = [],
        array $columns = []
    ) {
        $result = [];
        $skuWithQuotes = false;

        foreach ($skus as $sku) {
            if (strpos($sku, '"') !== false) {
                $skuWithQuotes = true;
                break;
            }
        }

        $skus = (empty($skus) || !$skuWithQuotes) ? [$skus] : array_chunk($skus, 500);

        foreach ($skus as $skusChunk) {
            $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
            $listingProductCollection->getSelect()->joinLeft(
                ['l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()],
                'l.id = main_table.listing_id',
                []
            );

            if (!empty($skusChunk)) {
                $skusChunk = array_map(function ($el) {
                    return (string)$el;
                }, $skusChunk);
                $listingProductCollection->addFieldToFilter('sku', ['in' => array_unique($skusChunk)]);
            }

            if (!empty($filters)) {
                foreach ($filters as $columnName => $columnValue) {
                    $listingProductCollection->addFieldToFilter($columnName, $columnValue);
                }
            }

            if (!empty($columns)) {
                $listingProductCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
                $listingProductCollection->getSelect()->columns($columns);
            }

            $result = array_merge(
                $result,
                $listingProductCollection->getData()
            );
        }

        return $result;
    }

    //########################################

    public function moveChildrenToListing(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $connection = $this->getConnection();

        // Get child products ids
        // ---------------------------------------
        $dbSelect = $connection->select()
            ->from(
                $this->activeRecordFactory->getObject('Amazon_Listing_Product')->getResource()->getMainTable(),
                ['listing_product_id', 'sku']
            )
            ->where('`variation_parent_id` = ?', $listingProduct->getId());
        $products = $connection->fetchPairs($dbSelect);

        if (!empty($products)) {
            $connection->update(
                $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable(),
                [
                    'listing_id' => $listingProduct->getListing()->getId()
                ],
                '`id` IN (' . implode(',', array_keys($products)) . ')'
            );
        }

        $dbSelect = $connection->select()
            ->from(
                $this->activeRecordFactory->getObject('Amazon\Item')->getResource()->getMainTable(),
                ['id']
            )
            ->where('`account_id` = ?', $listingProduct->getListing()->getAccountId())
            ->where('`marketplace_id` = ?', $listingProduct->getListing()->getMarketplaceId())
            ->where('`sku` IN (?)', implode(',', array_values($products)));
        $items = $connection->fetchCol($dbSelect);

        if (!empty($items)) {
            $connection->update(
                $this->activeRecordFactory->getObject('Amazon\Item')->getResource()->getMainTable(),
                [
                    'store_id' => $listingProduct->getListing()->getStoreId()
                ],
                '`id` IN ('.implode(',', $items).')'
            );
        }
    }

    //########################################

    public function mapChannelItemProduct(\Ess\M2ePro\Model\Amazon\Listing\Product $listingProduct)
    {
        $amazonItemTable = $this->activeRecordFactory->getObject('Amazon\Item')->getResource()->getMainTable();
        $existedRelation = $this->getConnection()
            ->select()
            ->from(['ei' => $amazonItemTable])
            ->where('`account_id` = ?', $listingProduct->getListing()->getAccountId())
            ->where('`marketplace_id` = ?', $listingProduct->getListing()->getMarketplaceId())
            ->where('`sku` = ?', $listingProduct->getSku())
            ->where('`product_id` = ?', $listingProduct->getParentObject()->getProductId())
            ->query()
            ->fetchColumn();

        if ($existedRelation) {
            return;
        }

        $this->getConnection()->update(
            $amazonItemTable,
            ['product_id' => $listingProduct->getParentObject()->getProductId()],
            [
                'account_id = ?' => $listingProduct->getListing()->getAccountId(),
                'marketplace_id = ?' => $listingProduct->getListing()->getMarketplaceId(),
                'sku = ?' => $listingProduct->getSku(),
                'product_id = ?' => $listingProduct->getParentObject()->getOrigData('product_id')
            ]
        );
    }

    //########################################
}
