<?php

namespace Ess\M2ePro\Model\ResourceModel\Config;

class Cache extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('m2epro_cache_config', 'id');
    }
}