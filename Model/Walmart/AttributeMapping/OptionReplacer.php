<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\AttributeMapping;

class OptionReplacer
{
    private bool $isPairsLoaded = false;
    private array $pairs;

    private \Ess\M2ePro\Model\AttributeOptionMapping\Repository $repository;

    public function __construct(\Ess\M2ePro\Model\AttributeOptionMapping\Repository $repository)
    {
        $this->repository = $repository;
    }

    public function replace(
        int $productTypeId,
        string $channelAttributeCode,
        int $magentoOptionId,
        string $defaultValue
    ): string {

        $pairs = $this->loadPairs();

        foreach ($pairs as $pair) {
            if (
                $pair->getProductTypeId() === $productTypeId
                && $pair->getChannelAttributeCode() === $channelAttributeCode
                && $pair->getMagentoOptionId() === $magentoOptionId
            ) {
                return $pair->getChannelOptionTitle();
            }
        }

        return $defaultValue;
    }

    /**
     * @return \Ess\M2ePro\Model\AttributeOptionMapping\Pair[]
     */
    private function loadPairs(): array
    {
        if (!$this->isPairsLoaded) {
            $this->pairs = $this->repository->findByComponentAndType(
                \Ess\M2ePro\Helper\Component\Walmart::NICK,
                \Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributesService::MAPPING_TYPE
            );
            $this->isPairsLoaded = true;
        }

        return $this->pairs;
    }
}
