<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Auto\Advanced;

class Filter extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_LISTING_AUTO_ADVANCED_FILTER_ID = 'listing_auto_advanced_filter_id';
    public const COLUMN_ADDING_PRODUCT_TYPE_TEMPLATE_ID = 'adding_product_type_template_id';

    protected function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_LISTING_AUTO_ADVANCED_FILTER,
            self::COLUMN_LISTING_AUTO_ADVANCED_FILTER_ID
        );
        $this->_isPkAutoIncrement = false;
    }
}
