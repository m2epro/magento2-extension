<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\LockItem;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractCollection
{
    //########################################

    public function _construct()
    {
        $this->_init(
            'Ess\M2ePro\Model\LockItem',
            'Ess\M2ePro\Model\ResourceModel\LockItem'
        );
    }

    //########################################
}