<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon\ProductType;

class AttributeMapping extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    protected function _construct()
    {
        $this->_init('m2epro_amazon_product_type_attribute_mapping', 'id');
    }

    public function loadByProductTypeAttributeCode(
        \Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping $object,
        string $productTypeAttributeCode
    ): self {
        return $this->load(
            $object,
            $productTypeAttributeCode,
            'product_type_attribute_code'
        );
    }
}
