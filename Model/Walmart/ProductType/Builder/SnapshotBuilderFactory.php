<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\ProductType\Builder;

class SnapshotBuilderFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(\Ess\M2ePro\Model\Walmart\ProductType $productType): SnapshotBuilder
    {
        return $this->objectManager->create(SnapshotBuilder::class)
                                   ->setModel($productType);
    }
}
