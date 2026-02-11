<?php

namespace Ess\M2ePro\Model\ResourceModel\Walmart;

use Magento\Framework\DB\Select;

class Listing extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_LISTING_ID = 'listing_id';
    public const COLUMN_AUTO_GLOBAL_ADDING_PRODUCT_TYPE_ID = 'auto_global_adding_product_type_id';
    public const COLUMN_AUTO_WEBSITE_ADDING_PRODUCT_TYPE_ID = 'auto_website_adding_product_type_id';

    public const COLUMN_TEMPLATE_SELLING_FORMAT_ID = 'template_selling_format_id';
    public const COLUMN_TEMPLATE_DESCRIPTION_ID = 'template_description_id';
    public const COLUMN_TEMPLATE_SYNCHRONIZATION_ID = 'template_synchronization_id';
    public const COLUMN_TEMPLATE_REPRICER_ID = 'template_repricer_id';

    public const COLUMN_CONDITION_MODE = 'condition_mode';
    public const COLUMN_CONDITION_VALUE = 'condition_value';
    public const COLUMN_CONDITION_CUSTOM_ATTRIBUTE = 'condition_custom_attribute';

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
