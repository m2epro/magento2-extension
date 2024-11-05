<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\AttributeMapping\Gpsr;

class Provider
{
    private const ATTRIBUTES = [
        [
            'title' => 'Manufacturer Company Name',
            'code' => 'Manufacturer Company Name',
        ],
        [
            'title' => 'Manufacturer Street 1',
            'code' => 'Manufacturer Street 1',
        ],
        [
            'title' => 'Manufacturer Street 2',
            'code' => 'Manufacturer Street 2',
        ],
        [
            'title' => 'Manufacturer City Name',
            'code' => 'Manufacturer City Name',
        ],
        [
            'title' => 'Manufacturer State or Province',
            'code' => 'Manufacturer State or Province',
        ],
        [
            'title' => 'Manufacturer Postal Code',
            'code' => 'Manufacturer Postal Code',
        ],
        [
            'title' => 'Manufacturer Country Code',
            'code' => 'Manufacturer Country Code',
        ],
        [
            'title' => 'Manufacturer Email',
            'code' => 'Manufacturer Email',
        ],
        [
            'title' => 'Manufacturer Phone',
            'code' => 'Manufacturer Phone',
        ],
    ];

    private \Ess\M2ePro\Model\AttributeMapping\Repository $attributeMappingRepository;

    public function __construct(\Ess\M2ePro\Model\AttributeMapping\Repository $attributeMappingRepository)
    {
        $this->attributeMappingRepository = $attributeMappingRepository;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[]
     */
    public function getAll(): array
    {
        return $this->retrieve(false);
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[]
     */
    public function getConfigured(): array
    {
        return $this->retrieve(true);
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[]
     */
    private function retrieve(bool $onlyConfigured): array
    {
        $existedByCode = $this->getExistedMappingGroupedByCode();

        $result = [];
        foreach (self::ATTRIBUTES as ['title' => $channelTitle, 'code' => $channelCode]) {
            $mappingId = null;
            $magentoAttributeCode = null;
            if (isset($existedByCode[$channelCode])) {
                $mappingId = $existedByCode[$channelCode]->getId();
                $magentoAttributeCode = $existedByCode[$channelCode]->getMagentoAttributeCode();
            }

            if (
                $mappingId === null
                && $onlyConfigured
            ) {
                continue;
            }

            $result[] = new \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair(
                $mappingId,
                \Ess\M2ePro\Model\Ebay\AttributeMapping\GpsrService::MAPPING_TYPE,
                $channelTitle,
                $channelCode,
                $magentoAttributeCode
            );
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

    // ----------------------------------------

    /**
     * @return string[]
     */
    public static function getAllAttributesCodes(): array
    {
        return array_column(self::ATTRIBUTES, 'code');
    }

    public static function getAttributeTitle(string $code): ?string
    {
        foreach (self::ATTRIBUTES as ['title' => $channelTitle, 'code' => $channelCode]) {
            if ($code !== $channelCode) {
                continue;
            }

            return $channelTitle;
        }

        return null;
    }
}
