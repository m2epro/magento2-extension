<?php

namespace Ess\M2ePro\Model\Amazon\ProductType\CategoryFinder;

class Repository
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace\CollectionFactory */
    private $marketplaceDictionaryCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType\CollectionFactory */
    private $dictionaryProductTypeCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory */
    private $templateProductTypeCollectionFactory;
    /** @var \Ess\M2ePro\Helper\Component\Amazon\ProductType */
    private $productTypeHelper;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace\CollectionFactory $marketplaceCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType\CollectionFactory $productTypeCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory $templateTypeCollectionFactory,
        \Ess\M2ePro\Helper\Component\Amazon\ProductType $productTypeHelper
    ) {
        $this->marketplaceDictionaryCollectionFactory = $marketplaceCollectionFactory;
        $this->dictionaryProductTypeCollectionFactory = $productTypeCollectionFactory;
        $this->templateProductTypeCollectionFactory = $templateTypeCollectionFactory;
        $this->productTypeHelper = $productTypeHelper;
    }

    /**
     * @param int $marketplaceId
     * @param array $nicks
     *
     * @return array<string, array{nick:string, title:string, templateId: int|null}>
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAvailableProductTypes(int $marketplaceId, array $nicks): array
    {
        $productTypes = $this->getProductTypesFromMarketplaceDictionary($marketplaceId);
        $alreadyUsedProductTypes = $this->productTypeHelper->getConfiguredProductTypesList($marketplaceId);

        $availableProductTypes = [];
        foreach ($nicks as $nick) {
            foreach ($productTypes as $productType) {
                if ($nick == $productType['nick']) {
                    $availableProductTypes[$nick] = [
                        'nick' => $productType['nick'],
                        'title' => $productType['title'],
                        'templateId' => $alreadyUsedProductTypes[$productType['nick']] ?? null,
                    ];
                }
            }
        }

        return $availableProductTypes;
    }

    /**
     * @param int $marketplaceId
     *
     * @return list<array{nick:string, title:string}>
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getProductTypesFromMarketplaceDictionary(int $marketplaceId): array
    {
        $collection = $this->marketplaceDictionaryCollectionFactory->create();
        $collection->appendFilterMarketplaceId($marketplaceId);

        /** @var \Ess\M2ePro\Model\Amazon\Dictionary\Marketplace $marketplaceDictionaryItem */
        $marketplaceDictionaryItem = $collection->getFirstItem();

        if ($marketplaceDictionaryItem === null) {
            return [];
        }

        $productTypes = $marketplaceDictionaryItem->getProductTypes();

        $result = [];
        foreach ($productTypes as $productType) {
            $result[] = [
                'nick' => $productType['nick'],
                'title' => $productType['title'],
            ];
        }

        return $result;
    }
}
