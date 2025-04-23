<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Listing;

class DiffFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(array $newSnapshotData, array $oldSnapshotData): Diff
    {
        $diff = $this->objectManager->create(Diff::class);
        $diff->setNewSnapshot($newSnapshotData);
        $diff->setOldSnapshot($oldSnapshotData);

        return $diff;
    }
}
