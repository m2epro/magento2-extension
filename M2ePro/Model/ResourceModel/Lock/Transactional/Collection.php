<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Lock\Transactional;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Lock\Transactional\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init(
            'Ess\M2ePro\Model\Lock\Transactional',
            'Ess\M2ePro\Model\ResourceModel\Lock\Transactional'
        );
    }

    //########################################
}
