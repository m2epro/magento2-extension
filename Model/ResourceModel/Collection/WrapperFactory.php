<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Collection;

class WrapperFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Wrapper
    {
        return $this->objectManager->create(Wrapper::class);
    }
}
