<?php

namespace Ess\M2ePro\Model\ResourceModel\Config\Module;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Ess\M2ePro\Model\Config\Module',
            'Ess\M2ePro\Model\ResourceModel\Config\Module'
        );
    }
}