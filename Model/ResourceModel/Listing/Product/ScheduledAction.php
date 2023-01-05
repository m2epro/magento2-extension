<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product;

class ScheduledAction extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    protected function _construct(): void
    {
        $this->_init('m2epro_listing_product_scheduled_action', 'id');
    }
}
