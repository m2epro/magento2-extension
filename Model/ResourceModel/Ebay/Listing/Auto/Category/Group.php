<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Auto\Category;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Auto\Category\Group
 */
class Group extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_listing_auto_category_group', 'listing_auto_category_group_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################
}
