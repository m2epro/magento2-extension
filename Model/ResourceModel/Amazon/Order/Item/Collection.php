<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Order\Item;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Amazon\Order\Item\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Child\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Amazon\Order\Item',
            'Ess\M2ePro\Model\ResourceModel\Amazon\Order\Item'
        );
    }

    //########################################
}
