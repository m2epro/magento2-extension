<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon;

use Magento\Framework\DB\Select;

class Listing extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_LISTING_ID = 'listing_id';
    public const COLUMN_RESTOCK_DATE_CUSTOM_ATTRIBUTE = 'restock_date_custom_attribute';
    public const COLUMN_GENERAL_ID_ATTRIBUTE = 'general_id_attribute';
    public const COLUMN_WORLDWIDE_ID_ATTRIBUTE = 'worldwide_id_attribute';
    public const COLUMN_OFFER_IMAGES = 'offer_images';
    public const COLUMN_AUTO_GLOBAL_ADDING_PRODUCT_TYPE_TEMPLATE_ID = 'auto_global_adding_product_type_template_id';
    public const COLUMN_AUTO_WEBSITE_ADDING_PRODUCT_TYPE_TEMPLATE_ID = 'auto_website_adding_product_type_template_id';

    /** @var bool */
    protected $_isPkAutoIncrement = false;

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_LISTING,
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
