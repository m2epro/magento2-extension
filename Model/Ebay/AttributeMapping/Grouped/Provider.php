<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\AttributeMapping\Grouped;

class Provider
{
    private const CODE_VARIATION_ATTRIBUTE = 'Variation Attribute';
    private const CODE_VARIATION_OPTION_TITLE = 'Variation Option Title';

    private const ATTRIBUTES = [
        [
            'title' => 'Variation Attribute',
            'code' => self::CODE_VARIATION_ATTRIBUTE,
        ],
        [
            'title' => 'Variation Option Title',
            'code' => self::CODE_VARIATION_OPTION_TITLE,
        ],
    ];

    private \Ess\M2ePro\Model\AttributeMapping\Repository $attributeMappingRepository;
    /** @var \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[]|null  */
    private ?array $cacheConfigured = null;

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

    public function findConfiguredVariationAttribute(): ?\Ess\M2ePro\Model\Ebay\AttributeMapping\Pair
    {
        return $this->findConfiguredByChannelCode(self::CODE_VARIATION_ATTRIBUTE);
    }

    public function findConfiguredVariationOptionTitle(): ?\Ess\M2ePro\Model\Ebay\AttributeMapping\Pair
    {
        return $this->findConfiguredByChannelCode(self::CODE_VARIATION_OPTION_TITLE);
    }

    private function findConfiguredByChannelCode(string $channelCode): ?\Ess\M2ePro\Model\Ebay\AttributeMapping\Pair
    {
        $configured = $this->getConfigured();
        foreach ($configured as $pair) {
            if ($pair->channelAttributeCode === $channelCode) {
                return $pair;
            }
        }

        return null;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair[]
     */
    private function getConfigured(): array
    {
        if ($this->cacheConfigured !== null) {
            return $this->cacheConfigured;
        }

        return $this->cacheConfigured = $this->retrieve(true);
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
                $magentoAttributeCode = $existedByCode[$channelCode]->getValue();
            }

            if (
                $mappingId === null
                && $onlyConfigured
            ) {
                continue;
            }

            $result[] = new \Ess\M2ePro\Model\Ebay\AttributeMapping\Pair(
                $mappingId,
                \Ess\M2ePro\Model\Ebay\AttributeMapping\GroupedService::MAPPING_TYPE,
                \Ess\M2ePro\Model\AttributeMapping\Pair::VALUE_MODE_ATTRIBUTE,
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
            \Ess\M2ePro\Model\Ebay\AttributeMapping\GroupedService::MAPPING_TYPE
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
