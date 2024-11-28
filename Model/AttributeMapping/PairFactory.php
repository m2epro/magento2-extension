<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AttributeMapping;

class PairFactory
{
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function createEmpty(): Pair
    {
        return $this->objectManager->create(Pair::class);
    }

    public function create(
        string $component,
        string $type,
        int $valueMode,
        string $channelAttributeTitle,
        string $channelAttributeCode,
        string $value
    ): Pair {
        $pair = $this->createEmpty();
        $pair->create($component, $type, $valueMode, $channelAttributeTitle, $channelAttributeCode, $value);

        return $pair;
    }
}
