<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon\ProductType;

class Validation extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    protected function _construct()
    {
        $this->_init('m2epro_amazon_product_type_validation', 'id');
    }
}
