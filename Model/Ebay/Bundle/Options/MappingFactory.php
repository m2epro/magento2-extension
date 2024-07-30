<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options;

class MappingFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Mapping
    {
        return $this->objectManager->create(Mapping::class);
    }
}
