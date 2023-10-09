<?php

namespace Ess\M2ePro\Model\Amazon\ProductType;

class AttributeMapping extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\AttributeMapping::class);
    }

    public function getProductTypeAttributeCode(): string
    {
        return (string)$this->getData('product_type_attribute_code');
    }

    public function setProductTypeAttributeCode(string $attributeCode): void
    {
        $this->setData('product_type_attribute_code', $attributeCode);
    }

    public function getProductTypeAttributeName(): string
    {
        return (string)$this->getData('product_type_attribute_name');
    }

    public function setProductTypeAttributeName(string $name): void
    {
        $this->setData('product_type_attribute_name', $name);
    }

    public function getMagentoAttributeCode(): string
    {
        return (string)$this->getData('magento_attribute_code');
    }

    public function setMagentoAttributeCode(string $attributeCode): void
    {
        $this->setData('magento_attribute_code', $attributeCode);
    }
}
