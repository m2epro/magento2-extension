<?php

namespace Ess\M2ePro\Model\ResourceModel\Config;

class Module extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('m2epro_module_config', 'id');
    }
}