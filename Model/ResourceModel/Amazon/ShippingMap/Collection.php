<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon\ShippingMap;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Child\AbstractModel
{
    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Model\Amazon\ShippingMap\AmazonShippingMap::class,
            \Ess\M2ePro\Model\ResourceModel\Amazon\ShippingMap::class
        );
    }
}
