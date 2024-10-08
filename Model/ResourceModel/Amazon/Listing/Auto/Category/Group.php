<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Auto\Category;

class Group extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_ADDING_PRODUCT_TYPE_TEMPLATE_ID  = 'adding_product_type_template_id';

    public function _construct()
    {
        $this->_init('m2epro_amazon_listing_auto_category_group', 'listing_auto_category_group_id');
        $this->_isPkAutoIncrement = false;
    }
}
