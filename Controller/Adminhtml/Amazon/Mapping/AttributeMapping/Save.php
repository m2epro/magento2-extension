<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Mapping\AttributeMapping;

class Save extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Mapping
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\AttributeMapping */
    private $attributeMappingResource;
    /** @var \Ess\M2ePro\Model\Amazon\ProductType\AttributeMappingFactory */
    private $attributeMappingFactory;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Amazon\ProductType\AttributeMapping $attributeMappingResource,
        \Ess\M2ePro\Model\Amazon\ProductType\AttributeMappingFactory $attributeMappingFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->attributeMappingResource = $attributeMappingResource;
        $this->attributeMappingFactory = $attributeMappingFactory;
    }

    public function execute()
    {
        $attributes = $this->getRequest()->getParam('attributes');
        if (!$attributes) {
            $this->setJsonContent(['success' => false]);

            return $this->getResult();
        }

        foreach ($attributes as $attributeMappingId => $magentoCode) {
            $attributeMapping = $this->attributeMappingFactory->create();
            $this->attributeMappingResource->load($attributeMapping, $attributeMappingId);

            if ($attributeMapping->getId() === null) {
                continue;
            }

            $attributeMapping->setMagentoAttributeCode($magentoCode);
            $this->attributeMappingResource->save($attributeMapping);
        }

        $this->setJsonContent(['success' => true]);

        return $this->getResult();
    }
}
