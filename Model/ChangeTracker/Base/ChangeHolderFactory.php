<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Base;

class ChangeHolderFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): ChangeHolder
    {
        return $this->objectManager->create(ChangeHolder::class);
    }
}
