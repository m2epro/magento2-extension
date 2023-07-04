<?php

namespace Ess\M2ePro\Model\Amazon\ProductType;

class ValidationFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Validation
    {
        return $this->objectManager->create(Validation::class);
    }
}
