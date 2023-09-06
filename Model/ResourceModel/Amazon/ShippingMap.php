<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon;

class ShippingMap extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected function _construct()
    {
        $this->_init('m2epro_amazon_shipping_map', 'id');
    }
}
