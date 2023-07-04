<?php

namespace Ess\M2ePro\Model\Ebay\Template\Shipping;

class CalculatedFactory
{
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $data = []): \Ess\M2ePro\Model\Ebay\Template\Shipping\Calculated
    {
        return $this->objectManager->create(Calculated::class, $data);
    }
}
