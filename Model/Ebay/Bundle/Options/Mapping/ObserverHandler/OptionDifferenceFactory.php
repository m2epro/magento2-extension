<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\ObserverHandler;

class OptionDifferenceFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function createAddedDiff(string $newTitle): OptionDifference
    {
        return $this->objectManager->create(OptionDifference::class, [
            'type' => OptionDifference::TYPE_ADDED,
            'newTitle' => $newTitle,
        ]);
    }

    public function createUpdatedDiff(string $oldTitle, string $newTitle): OptionDifference
    {
        return $this->objectManager->create(OptionDifference::class, [
            'type' => OptionDifference::TYPE_UPDATED,
            'oldTitle' => $oldTitle,
            'newTitle' => $newTitle,
        ]);
    }

    public function createDeletedDiff(string $oldTitle): OptionDifference
    {
        return $this->objectManager->create(OptionDifference::class, [
            'type' => OptionDifference::TYPE_DELETED,
            'oldTitle' => $oldTitle,
        ]);
    }
}
