<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\AttributeMapping;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping::class,
            \Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\AttributeMapping::class
        );
    }
}
