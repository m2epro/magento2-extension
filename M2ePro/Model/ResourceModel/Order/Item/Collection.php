<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Order\Item[] getItems()
 */
namespace Ess\M2ePro\Model\ResourceModel\Order\Item;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Order\Item\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Parent\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Order\Item',
            'Ess\M2ePro\Model\ResourceModel\Order\Item'
        );
    }

    //########################################
}
