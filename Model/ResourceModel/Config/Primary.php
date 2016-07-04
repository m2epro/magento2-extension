<?php

namespace Ess\M2ePro\Model\ResourceModel\Config;

class Primary extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('m2epro_primary_config', 'id');
    }
}