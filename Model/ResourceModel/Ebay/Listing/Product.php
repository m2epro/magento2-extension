<?php

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing;

class Product extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_LISTING_PRODUCT_ID = 'listing_product_id';
    public const COLUMN_EBAY_ITEM_ID = 'ebay_item_id';
    public const COLUMN_TEMPLATE_SYNCHRONIZATION_ID = 'template_synchronization_id';
    public const COLUMN_ONLINE_PRODUCT_IDENTIFIERS_HASH = 'online_product_identifiers_hash';
    public const COLUMN_ONLINE_DESCRIPTION = 'online_description';

    public const COLUMN_PRICE_LAST_UPDATE_DATE = 'price_last_update_date';

    public const COLUMN_KTYPES_RESOLVE_STATUS = 'ktypes_resolve_status';
    public const COLUMN_KTYPES_RESOLVE_LAST_TRY_DATE = 'ktypes_resolve_last_try_date';
    public const COLUMN_KTYPES_RESOLVE_ATTEMPT = 'ktypes_resolve_attempt';

    public const COLUMN_VIDEO_URL = 'video_url';
    public const COLUMN_VIDEO_ID = 'video_id';
    public const COLUMN_ONLINE_VIDEO_ID = 'online_video_id';

    public const COLUMN_COMPLIANCE_DOCUMENTS = 'compliance_documents';
    public const COLUMN_ONLINE_COMPLIANCE_DOCUMENTS = 'online_compliance_documents';

    /** @var bool  */
    protected $_isPkAutoIncrement = false;

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_LISTING_PRODUCT,
            self::COLUMN_LISTING_PRODUCT_ID
        );
        $this->_isPkAutoIncrement = false;
    }

    public function getTemplateCategoryIds(array $listingProductIds, $columnName, $returnNull = false)
    {
        $select = $this->getConnection()
                       ->select()
                       ->from(['elp' => $this->getMainTable()])
                       ->reset(\Magento\Framework\DB\Select::COLUMNS)
                       ->columns([$columnName])
                       ->where('listing_product_id IN (?)', $listingProductIds);

        !$returnNull && $select->where("{$columnName} IS NOT NULL");

        foreach ($select->query()->fetchAll() as $row) {
            $id = $row[$columnName] !== null ? (int)$row[$columnName] : null;
            if (!$returnNull) {
                continue;
            }

            $ids[$id] = $id;
        }

        return array_values($ids);
    }

    public function assignTemplatesToProducts(
        $productsIds,
        $categoryTemplateId = null,
        $categorySecondaryTemplateId = null,
        $storeCategoryTemplateId = null,
        $storeCategorySecondaryTemplateId = null
    ) {
        if (empty($productsIds)) {
            return;
        }

        $bind = [
            'template_category_id' => $categoryTemplateId,
            'template_category_secondary_id' => $categorySecondaryTemplateId,
            'template_store_category_id' => $storeCategoryTemplateId,
            'template_store_category_secondary_id' => $storeCategorySecondaryTemplateId,
        ];
        $bind = array_filter($bind);

        $this->getConnection()->update(
            $this->getMainTable(),
            $bind,
            ['listing_product_id IN (?)' => $productsIds]
        );
    }

    public function mapChannelItemProduct(\Ess\M2ePro\Model\Ebay\Listing\Product $listingProduct)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Item $ebayItem */
        $ebayItem = $this->activeRecordFactory->getObjectLoaded(
            'Ebay\Item',
            $listingProduct->getEbayItemId()
        );
        $ebayItemTable = $this->activeRecordFactory->getObject('Ebay\Item')->getResource()->getMainTable();
        $existedRelation = $this->getConnection()
                                ->select()
                                ->from(['ei' => $ebayItemTable])
                                ->where('`account_id` = ?', $ebayItem->getAccountId())
                                ->where('`marketplace_id` = ?', $ebayItem->getMarketplaceId())
                                ->where('`item_id` = ?', $ebayItem->getItemId())
                                ->where('`product_id` = ?', $listingProduct->getParentObject()->getProductId())
                                ->where('`store_id` = ?', $ebayItem->getStoreId())
                                ->query()
                                ->fetchColumn();

        if ($existedRelation) {
            return;
        }

        $this->getConnection()->update(
            $ebayItemTable,
            ['product_id' => $listingProduct->getParentObject()->getProductId()],
            ['id = ?' => $listingProduct->getEbayItemId()]
        );
    }
}
