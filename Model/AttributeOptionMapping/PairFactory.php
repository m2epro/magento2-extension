<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\AttributeOptionMapping;

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
        int $productTypeId,
        string $channelAttributeTitle,
        string $channelAttributeCode,
        string $channelOptionTitle,
        string $channelOptionCode,
        string $magentoAttributeCode,
        int $magentoOptionId,
        string $magentoOptionTitle
    ): Pair {
        $pair = $this->createEmpty();
        $pair->create(
            $component,
            $type,
            $productTypeId,
            $channelAttributeTitle,
            $channelAttributeCode,
            $channelOptionTitle,
            $channelOptionCode,
            $magentoAttributeCode,
            $magentoOptionId,
            $magentoOptionTitle
        );

        return $pair;
    }
}
