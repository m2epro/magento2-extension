<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Auto;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing\Auto\Category
 */
class Category extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_listing_auto_category', 'id');
    }

    //########################################
}
