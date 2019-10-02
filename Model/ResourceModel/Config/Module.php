<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Config;

/**
 * Class Module
 * @package Ess\M2ePro\Model\ResourceModel\Config
 */
class Module extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    // ########################################

    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('m2epro_module_config', 'id');
    }

    // ########################################
}
