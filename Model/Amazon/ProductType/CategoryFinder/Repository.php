<?php

namespace Ess\M2ePro\Model\Amazon\ProductType\CategoryFinder;

class Repository
{
    private \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace\Repository $dictionaryMarketplaceRepository;
    private \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository,
        \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace\Repository $dictionaryMarketplaceRepository
    ) {
        $this->dictionaryMarketplaceRepository = $dictionaryMarketplaceRepository;
        $this->templateProductTypeRepository = $templateProductTypeRepository;
    }

    /**
     * @param int $marketplaceId
     * @param array $nicks
     *
     * @return array<string, array{nick:string, title:string, templateId: int|null}>
     */
    public function getAvailableProductTypes(int $marketplaceId, array $nicks): array
    {
        $productTypesMap = $this->getProductTypesFromMarketplaceDictionary($marketplaceId);
        $alreadyUsedProductTypesMap = $this->getProductTypesTemplates($marketplaceId);

        $availableProductTypes = [];
        foreach ($nicks as $nick) {
            if (isset($productTypesMap[$nick])) {
                $availableProductTypes[$nick] = [
                    'nick' => $productTypesMap[$nick]['nick'],
                    'title' => $productTypesMap[$nick]['title'],
                    'templateId' => $alreadyUsedProductTypesMap[$productTypesMap[$nick]['nick']] ?? null,
                ];
            }
        }

        return $availableProductTypes;
    }

    private function getProductTypesFromMarketplaceDictionary(int $marketplaceId): array
    {
        $dictionary = $this->dictionaryMarketplaceRepository->findByMarketplaceId($marketplaceId);
        if ($dictionary === null) {
            return [];
        }

        $productTypes = $dictionary->getProductTypes();

        $result = [];
        foreach ($productTypes as $productType) {
            $result[$productType['nick']] = [
                'nick' => $productType['nick'],
                'title' => $productType['title'],
            ];
        }

        return $result;
    }

    private function getProductTypesTemplates(int $marketplaceId): array
    {
        $result = [];
        foreach ($this->templateProductTypeRepository->findByMarketplaceId($marketplaceId) as $productType) {
            $result[$productType->getNick()] = (int)$productType->getId();
        }

        return $result;
    }
}
