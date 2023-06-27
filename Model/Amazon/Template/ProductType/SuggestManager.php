<?php

namespace Ess\M2ePro\Model\Amazon\Template\ProductType;

class SuggestManager
{
    /** @var \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory */
    private $amazonDispatcherFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory */
    private $marketplaceCollectionFactory;
    /** @var \Ess\M2ePro\Helper\Component\Amazon\ProductType */
    private $productTypeHelper;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $amazonDispatcherFactory,
        \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory,
        \Ess\M2ePro\Helper\Component\Amazon\ProductType $productTypeHelper
    ) {
        $this->amazonDispatcherFactory = $amazonDispatcherFactory;
        $this->marketplaceCollectionFactory = $marketplaceCollectionFactory;
        $this->productTypeHelper = $productTypeHelper;
    }

    public function getProductTypes(
        int $marketplaceId,
        array $keywords
    ): array {
        if (empty($keywords)) {
            return [];
        }

        $marketplaceCollection = $this->marketplaceCollectionFactory
            ->createWithAmazonChildMode()
            ->addFieldToFilter('id', $marketplaceId);
        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $marketplaceCollection->getFirstItem();
        if (!$marketplace->getId()) {
            return [];
        }

        $marketplaceDictionary = $this->productTypeHelper->getMarketplaceDictionary($marketplaceId);
        $dictionaryProductTypes = $marketplaceDictionary->getProductTypes();
        if (empty($dictionaryProductTypes)) {
            return [];
        }

        $requestData = [
            'marketplace_id' => $marketplace->getNativeId(),
            'keywords' => $keywords,
        ];

        $dispatcher = $this->amazonDispatcherFactory->create();
        /** @var \Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByKeywords\ItemsRequester $connector */
        $connector = $dispatcher->getConnector(
            'productType',
            'searchByKeywords',
            'itemsRequester',
            $requestData
        );

        $dispatcher->process($connector);
        $suggestedProductTypes = $connector->getResponseData();

        $result = [];
        $alreadyUsedProductTypes = $this->productTypeHelper->getConfiguredProductTypesList($marketplaceId);
        foreach ($suggestedProductTypes as $nick) {
            if (empty($dictionaryProductTypes[$nick])) {
                continue;
            }

            $productTypeData = [
                'nick' => $nick,
                'title' => $dictionaryProductTypes[$nick]['title'],
            ];

            if (!empty($alreadyUsedProductTypes[$nick])) {
                $productTypeData['exist_product_type_id'] = $alreadyUsedProductTypes[$nick];
            }

            $result[] = $productTypeData;
        }

        usort(
            $result,
            static function ($a, $b) {
                return $a['title'] <=> $b['title'];
            }
        );

        return $result;
    }
}
