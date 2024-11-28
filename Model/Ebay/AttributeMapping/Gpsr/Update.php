<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr;

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
     *
     * @return int - processed (updated or created) count
     */
    public function process(array $attributesMapping): int
    {
        $attributesMapping = $this->removeUnknownAttributes($attributesMapping);
        $existedByAttributeCode = $this->getExistedMappingGroupedByCode();

        $processedCount = 0;
        foreach ($attributesMapping as $newPair) {
            $exist = $existedByAttributeCode[$newPair->channelAttributeCode] ?? null;
            if ($exist === null) {
                $new = $this->mappingFactory->create(
                    \Ess\M2ePro\Helper\Component\Ebay::NICK,
                    \Ess\M2ePro\Model\Ebay\AttributeMapping\GpsrService::MAPPING_TYPE,
                    $newPair->mode,
                    $newPair->channelAttributeTitle,
                    $newPair->channelAttributeCode,
                    $newPair->value
                );

                $this->attributeMappingRepository->create($new);

                $processedCount++;

                continue;
            }

            unset($existedByAttributeCode[$newPair->channelAttributeCode]);

            if (
                $exist->getValue() === $newPair->value
                && $exist->getValueMode() === $newPair->mode
            ) {
                continue;
            }

            $exist->setValueMode($newPair->mode);
            $exist->setValue($newPair->value);

            $this->attributeMappingRepository->save($exist);

            $processedCount++;
        }

        if (!empty($existedByAttributeCode)) {
            foreach ($existedByAttributeCode as $someOld) {
                $this->attributeMappingRepository->remove($someOld);
            }
        }

        return $processedCount;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[] $attributesMapping
     *
     * @return \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[]
     */
    private function removeUnknownAttributes(array $attributesMapping): array
    {
        $result = [];
        $knownAttributes = \Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr\Provider::getAllAttributesCodes();
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
            \Ess\M2ePro\Model\Ebay\AttributeMapping\GpsrService::MAPPING_TYPE
        );
        foreach ($existed as $pair) {
            $result[$pair->getChannelAttributeCode()] = $pair;
        }

        return $result;
    }
}
