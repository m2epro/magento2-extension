<?php

namespace Ess\M2ePro\Model\Amazon\ProductType\AttributeMapping;

class ManagerFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(\Ess\M2ePro\Model\Amazon\Template\ProductType $productType): Manager
    {
        return $this->objectManager->create(Manager::class, [
            'productType' => $productType,
        ]);
    }
}
