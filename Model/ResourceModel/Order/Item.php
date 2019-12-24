<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Order;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Order\Item
 */
class Item extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Parent\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_order_item', 'id');
    }

    //########################################
}
