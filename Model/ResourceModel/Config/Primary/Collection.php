<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Config\Primary;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Config\Primary\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    // ########################################

    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Ess\M2ePro\Model\Config\Primary',
            'Ess\M2ePro\Model\ResourceModel\Config\Primary'
        );
    }

    // ########################################
}
