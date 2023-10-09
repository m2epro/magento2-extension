<?php

namespace Ess\M2ePro\Model\Amazon\ProductType;

class AttributeMappingFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): AttributeMapping
    {
        return $this->objectManager->create(AttributeMapping::class);
    }
}
