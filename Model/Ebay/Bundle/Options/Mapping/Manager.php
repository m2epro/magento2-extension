<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping;

class Manager
{
    /** @var \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\Repository */
    private Repository $mappingRepository;
    private \Ess\M2ePro\Model\Ebay\Bundle\Options\MappingFactory $mappingFactory;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\Repository $mappingRepository,
        \Ess\M2ePro\Model\Ebay\Bundle\Options\MappingFactory $mappingFactory
    ) {
        $this->mappingRepository = $mappingRepository;
        $this->mappingFactory = $mappingFactory;
    }

    public function save(string $title, string $attributeCode): ?\Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping
    {
        $existedMappings = $this->getExistedMappingsByTitle();
        $existMapping = $existedMappings[$title] ?? null;

        if ($existMapping === null) {
            if (empty($attributeCode)) {
                return null;
            }

            return $this->createMapping($title, $attributeCode);
        }

        if (!empty($attributeCode)) {
            if ($existMapping->getAttributeCode() === $attributeCode) {
                return null;
            }

            return $this->updateMappingAttributeCode($existMapping, $attributeCode);
        }

        return $this->deleteMapping($existMapping);
    }

    /**
     * @return array<string, \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping>
     */
    private function getExistedMappingsByTitle(): array
    {
        $allMappings = $this->mappingRepository->getAll();

        $result = [];
        foreach ($allMappings as $mapping) {
            $result[$mapping->getTitle()] = $mapping;
        }

        return $result;
    }

    private function createMapping(string $title, string $attributeCode): \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping
    {
        $mapping = $this->mappingFactory->create();
        $mapping->init($title, $attributeCode);

        $this->mappingRepository->create($mapping);

        return $mapping;
    }

    private function updateMappingAttributeCode(
        \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping $mapping,
        string $attributeCode
    ): \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping {
        $mapping->setAttributeCode($attributeCode);

        $this->mappingRepository->save($mapping);

        return $mapping;
    }

    private function deleteMapping(
        \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping $mapping
    ): \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping {
        $this->mappingRepository->delete($mapping);

        return $mapping;
    }
}
