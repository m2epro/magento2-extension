<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\AttributeMapping\Grouped;

class Update
{
    private \Ess\M2ePro\Model\AttributeMapping\Repository $attributeMappingRepository;
    private \Ess\M2ePro\Model\AttributeMapping\PairFactory $mappingFactory;

    public function __construct(
        \Ess\M2ePro\Model\AttributeMapping\Repository $attributeMappingRepository,
        \Ess\M2ePro\Model\AttributeMapping\PairFactory $mappingFactory
    ) {
        $this->attributeMappingRepository = $attributeMappingRepository;
        $this->mappingFactory = $mappingFactory;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[] $attributesMapping
     */
    public function process(array $attributesMapping): void
    {
        $attributesMapping = $this->removeUnknownAttributes($attributesMapping);
        $existedByAttributeCode = $this->getExistedMappingGroupedByCode();

        foreach ($attributesMapping as $newPair) {
            $exist = $existedByAttributeCode[$newPair->channelAttributeCode] ?? null;
            if ($exist === null) {
                $mapping = $this->createMapping($newPair);
                $this->attributeMappingRepository->create($mapping);

                continue;
            }

            unset($existedByAttributeCode[$newPair->channelAttributeCode]);

            if ($exist->getValue() === $newPair->value) {
                continue;
            }

            $exist->setValue($newPair->value);

            $this->attributeMappingRepository->save($exist);
        }

        if (!empty($existedByAttributeCode)) {
            foreach ($existedByAttributeCode as $someOld) {
                $this->attributeMappingRepository->remove($someOld);
            }
        }
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[] $attributesMapping
     *
     * @return \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[]
     */
    private function removeUnknownAttributes(array $attributesMapping): array
    {
        $result = [];
        $knownAttributes = \Ess\M2ePro\Model\Ebay\AttributeMapping\Grouped\Provider::getAllAttributesCodes();
        foreach ($attributesMapping as $pair) {
            if (!in_array($pair->channelAttributeCode, $knownAttributes, true)) {
                continue;
            }

            $result[] = $pair;
        }

        return $result;
    }

    /**
     * @return \Ess\M2ePro\Model\AttributeMapping\Pair[]
     */
    private function getExistedMappingGroupedByCode(): array
    {
        $result = [];

        $existed = $this->attributeMappingRepository->findByComponentAndType(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            \Ess\M2ePro\Model\Ebay\AttributeMapping\GroupedService::MAPPING_TYPE
        );
        foreach ($existed as $pair) {
            $result[$pair->getChannelAttributeCode()] = $pair;
        }

        return $result;
    }

    private function createMapping(
        \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair $ebayPair
    ): \Ess\M2ePro\Model\AttributeMapping\Pair {
        return $this->mappingFactory->create(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            \Ess\M2ePro\Model\Ebay\AttributeMapping\GroupedService::MAPPING_TYPE,
            \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_ATTRIBUTE,
            $ebayPair->channelAttributeTitle,
            $ebayPair->channelAttributeCode,
            $ebayPair->value
        );
    }
}
