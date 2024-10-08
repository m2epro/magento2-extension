<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\ProductType\Builder;

class DiffFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(): Diff
    {
        return $this->objectManager->create(Diff::class);
    }
}
