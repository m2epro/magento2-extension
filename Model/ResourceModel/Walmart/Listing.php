<?php

namespace Ess\M2ePro\Model\ResourceModel\Walmart;

use Magento\Framework\DB\Select;

class Listing extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_LISTING_ID = 'listing_id';
    public const COLUMN_AUTO_GLOBAL_ADDING_PRODUCT_TYPE_ID = 'auto_global_adding_product_type_id';
    public const COLUMN_AUTO_WEBSITE_ADDING_PRODUCT_TYPE_ID = 'auto_website_adding_product_type_id';

    /** @var bool  */
    protected $_isPkAutoIncrement = false;

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_LISTING,
            self::COLUMN_LISTING_ID
        );
    }

    public function getUsedProductsIds($listingId)
    {
        $collection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('listing_id', $listingId);

        $collection->distinct(true);

        $collection->getSelect()->reset(Select::COLUMNS);
        $collection->getSelect()->columns(['product_id']);

        return $collection->getColumnValues('product_id');
    }
}
