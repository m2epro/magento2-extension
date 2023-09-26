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

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\Marketplace\CollectionFactory $marketplaceCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary\ProductType\CollectionFactory $productTypeCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Template\ProductType\CollectionFactory $templateTypeCollectionFactory
    ) {
        $this->marketplaceDictionaryCollectionFactory = $marketplaceCollectionFactory;
        $this->dictionaryProductTypeCollectionFactory = $productTypeCollectionFactory;
        $this->templateProductTypeCollectionFactory = $templateTypeCollectionFactory;
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
        $templateIds = $this->getTemplateIdsOfProductTypes(array_column($productTypes, 'nick'));

        $availableProductTypes = [];
        foreach ($nicks as $nick) {
            foreach ($productTypes as $productType) {
                if ($nick == $productType['nick']) {
                    $availableProductTypes[$nick] = [
                        'nick' => $productType['nick'],
                        'title' => $productType['title'],
                        'templateId' => $templateIds[$productType['nick']] ?? null,
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

    /**
     * @param string[] $nicksOfExistedProductTypes
     *
     * @return array<string, string|null>
     */
    private function getTemplateIdsOfProductTypes(array $nicksOfExistedProductTypes): array
    {
        $dictionaryProductTypeCollection = $this->dictionaryProductTypeCollectionFactory->create();
        $templateProductTypeCollection = $this->templateProductTypeCollectionFactory->create();

        $select = $dictionaryProductTypeCollection->getSelect();
        $select->reset();
        $select->from(['dpt' => $dictionaryProductTypeCollection->getMainTable()], ['nick']);

        $nicks = $dictionaryProductTypeCollection->getConnection()->quote($nicksOfExistedProductTypes);
        $select->joinLeft(
            ['tpt' => $templateProductTypeCollection->getMainTable()],
            new \Zend_Db_Expr(
                sprintf('tpt.dictionary_product_type_id = dpt.id AND dpt.nick IN (%s)', $nicks)
            ),
            ['id' => 'tpt.id']
        );

        $templateIds = [];
        $allItems = $dictionaryProductTypeCollection->getConnection()->fetchAll(
            $dictionaryProductTypeCollection->getSelect()
        );

        foreach ($allItems as $item) {
            $templateIds[$item['nick']] = $item['id'];
        }

        return $templateIds;
    }
}
