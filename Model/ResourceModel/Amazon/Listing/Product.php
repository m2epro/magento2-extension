<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing;

class Product extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_LISTING_PRODUCT_ID = 'listing_product_id';
    public const COLUMN_TEMPLATE_PRODUCT_TYPE_ID = 'template_product_type_id';
    public const COLUMN_ONLINE_REGULAR_MAP_PRICE = 'online_regular_map_price';
    public const COLUMN_IS_VARIATION_PARENT = 'is_variation_parent';
    public const COLUMN_TEMPLATE_SHIPPING_ID = 'template_shipping_id';
    public const COLUMN_SKU = 'sku';
    public const COLUMN_IS_AFN_CHANNEL = 'is_afn_channel';
    public const COLUMN_ONLINE_AFN_QTY = 'online_afn_qty';
    public const COLUMN_GENERAL_ID = 'general_id';
    public const COLUMN_IS_GENERAL_ID_OWNER = 'is_general_id_owner';

    /** @var bool  */
    protected $_isPkAutoIncrement = false;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory  */
    private $amazonFactory;

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

    public function _construct(): void
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_LISTING_PRODUCT,
            self::COLUMN_LISTING_PRODUCT_ID
        );
        $this->_isPkAutoIncrement = false;
    }

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
                $listingProductCollection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
                $listingProductCollection->getSelect()->columns($columns);
            }

            $result = array_merge(
                $result,
                $listingProductCollection->getData()
            );
        }

        return $result;
    }

    public function moveChildrenToListing(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $connection = $this->getConnection();

        // Get child products ids
        // ---------------------------------------
        $dbSelect = $connection->select()
                               ->from(
                                   $this->activeRecordFactory->getObject('Amazon_Listing_Product')
                                                             ->getResource()
                                                             ->getMainTable(),
                                   ['listing_product_id', 'sku']
                               )
                               ->where('`variation_parent_id` = ?', $listingProduct->getId());
        $products = $connection->fetchPairs($dbSelect);

        if (!empty($products)) {
            $connection->update(
                $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable(),
                [
                    'listing_id' => $listingProduct->getListing()->getId(),
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
                    'store_id' => $listingProduct->getListing()->getStoreId(),
                ],
                '`id` IN (' . implode(',', $items) . ')'
            );
        }
    }

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
                'product_id = ?' => $listingProduct->getParentObject()->getOrigData('product_id'),
            ]
        );
    }
}
