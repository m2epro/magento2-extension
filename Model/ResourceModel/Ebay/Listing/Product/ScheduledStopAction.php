<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product;

class ScheduledStopAction extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public function _construct()
    {
        $this->_init('m2epro_ebay_listing_product_scheduled_stop_action', 'id');
    }
}
