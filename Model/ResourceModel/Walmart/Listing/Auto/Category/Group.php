<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Auto\Category;

class Group extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_LISTING_AUTO_CATEGORY_GROUP_ID = 'listing_auto_category_group_id';
    public const COLUMN_ADDING_PRODUCT_TYPE_ID = 'adding_product_type_id';

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_LISTING_AUTO_CATEGORY_GROUP,
            self::COLUMN_LISTING_AUTO_CATEGORY_GROUP_ID
        );
        $this->_isPkAutoIncrement = false;
    }
}
