<?php

namespace Ess\M2ePro\Model\ResourceModel\Config\Primary;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractCollection
{
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
}