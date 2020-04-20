<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing\Product\ScheduledAction
 */
class ScheduledAction extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_listing_product_scheduled_action', 'id');
    }

    //########################################
}
