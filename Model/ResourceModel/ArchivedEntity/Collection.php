<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\ArchivedEntity;

/**
 * Class Collection
 * @package Ess\M2ePro\Model\ResourceModel\ArchivedEntity
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init(
            'Ess\M2ePro\Model\ArchivedEntity',
            'Ess\M2ePro\Model\ResourceModel\ArchivedEntity'
        );
    }

    //########################################
}
