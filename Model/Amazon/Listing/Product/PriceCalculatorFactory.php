<?php

namespace Ess\M2ePro\Model\Amazon\Listing\Product;

class PriceCalculatorFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): PriceCalculator
    {
        return $this->objectManager->create(PriceCalculator::class, $data);
    }
}
