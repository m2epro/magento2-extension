<?php

namespace Ess\M2ePro\Model\ResourceModel\Config;

class Synchronization extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('m2epro_synchronization_config', 'id');
    }
}