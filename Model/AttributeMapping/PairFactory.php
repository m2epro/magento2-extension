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
        string $channelAttributeTitle,
        string $channelAttributeCode,
        string $magentoAttributeCode
    ): Pair {
        $pair = $this->createEmpty();
        $pair->create($component, $type, $channelAttributeTitle, $channelAttributeCode, $magentoAttributeCode);

        return $pair;
    }
}
