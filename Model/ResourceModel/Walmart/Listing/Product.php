<?php

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Listing;

class Product extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_LISTING_PRODUCT_ID = 'listing_product_id';
    public const COLUMN_PRODUCT_TYPE_ID = 'product_type_id';
    public const COLUMN_IS_NOT_MAPPED_TO_EXISTING_CHANNEL_ITEM = 'is_not_mapped_to_existing_channel_item';
    public const COLUMN_ONLINE_REPRICER_STRATEGY_NAME = 'online_repricer_strategy_name';
    public const COLUMN_ONLINE_REPRICER_MIN_PRICE = 'online_repricer_min_price';
    public const COLUMN_ONLINE_REPRICER_MAX_PRICE = 'online_repricer_max_price';
    public const COLUMN_REPRICER_LAST_UPDATE_DATE = 'repricer_last_update_date';
    public const COLUMN_TEMPLATE_REPRICER_ID = 'template_repricer_id';

    private \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource;
    protected $_isPkAutoIncrement = false;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->listingProductResource = $listingProductResource;

        parent::__construct(
            $helperFactory,
            $activeRecordFactory,
            $parentFactory,
            $context,
            $connectionName
        );
    }

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_LISTING_PRODUCT,
            self::COLUMN_LISTING_PRODUCT_ID
        );
        $this->_isPkAutoIncrement = false;
    }

    public function mapChannelItemProduct(\Ess\M2ePro\Model\Walmart\Listing\Product $listingProduct): void
    {
        $walmartItemTable = $this->activeRecordFactory->getObject('Walmart\Item')->getResource()->getMainTable();

        $existedRelation = $this->getConnection()
                                ->select()
                                ->from(['ei' => $walmartItemTable])
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
            $walmartItemTable,
            ['product_id' => $listingProduct->getParentObject()->getProductId()],
            [
                'account_id = ?' => $listingProduct->getListing()->getAccountId(),
                'marketplace_id = ?' => $listingProduct->getListing()->getMarketplaceId(),
                'sku = ?' => $listingProduct->getSku(),
                'product_id = ?' => $listingProduct->getParentObject()->getOrigData('product_id'),
            ]
        );
    }

    public function moveChildrenToListing(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->join(
            ['wlp' => $this->getMainTable()],
            'lp.id = wlp.listing_product_id',
            null
        );
        $select->join(
            ['parent_lp' => $this->listingProductResource->getMainTable()],
            'parent_lp.id = wlp.variation_parent_id',
            ['listing_id' => 'parent_lp.listing_id']
        );
        $select->where('wlp.variation_parent_id = ?', $listingProduct->getId());

        $updateQuery = $connection->updateFromSelect(
            $select,
            ['lp' => $this->listingProductResource->getMainTable()]
        );

        $connection->query($updateQuery);
    }
}
