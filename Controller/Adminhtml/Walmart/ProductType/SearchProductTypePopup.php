<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\ProductType;

class SearchProductTypePopup extends \Ess\M2ePro\Controller\Adminhtml\Walmart\AbstractProductType
{
    private \Ess\M2ePro\Model\Walmart\Dictionary\Marketplace\Repository $marketplaceDictionaryRepository;
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Dictionary\Marketplace\Repository $marketplaceDictionaryRepository,
        \Ess\M2ePro\Model\Walmart\Dictionary\ProductType\Repository $productTypeDictionaryRepository,
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->marketplaceDictionaryRepository = $marketplaceDictionaryRepository;
        $this->productTypeRepository = $productTypeRepository;
    }

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        if (!$marketplaceId) {
            $this->setJsonContent([
                'result' => false,
                'message' => 'You should provide correct marketplace_id.',
            ]);

            return $this->getResult();
        }

        $productTypes = $this->getAvailableProductTypes((int)$marketplaceId);

        /** @var \Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Edit\Tabs\General\SearchPopup $block */
        $block = $this->getLayout()
                      ->createBlock(
                          \Ess\M2ePro\Block\Adminhtml\Walmart\ProductType\Edit\Tabs\General\SearchPopup::class
                      );
        $block->setProductTypes($productTypes);

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    private function getAvailableProductTypes(int $marketplaceId): array
    {
        $marketplaceDictionary = $this->marketplaceDictionaryRepository->findByMarketplaceId($marketplaceId);
        if ($marketplaceDictionary === null) {
            return [];
        }

        $marketplaceDictionaryProductTypes = $marketplaceDictionary->getProductTypes();
        if (empty($marketplaceDictionaryProductTypes)) {
            return [];
        }
        $marketplaceDictionaryProductTypes = $this->sortMarketplaceDictionaryProductTypes(
            $marketplaceDictionaryProductTypes
        );

        $result = [];
        $productTypes = $this->productTypeRepository->retrieveListWithKeyNick($marketplaceId);
        foreach ($marketplaceDictionaryProductTypes as $marketplaceDictionaryProductType) {
            $productTypeData = [
                'nick' => $marketplaceDictionaryProductType['nick'],
                'title' => $marketplaceDictionaryProductType['title'],
            ];

            $existsProductType = $productTypes[$marketplaceDictionaryProductType['nick']] ?? null;
            if ($existsProductType !== null) {
                $productTypeData['exist_product_type_id'] = $existsProductType->getId();
            }
            $result[] = $productTypeData;
        }

        return $result;
    }

    private function sortMarketplaceDictionaryProductTypes(array $productTypes): array
    {
        $byTitle = [];
        foreach ($productTypes as $productType) {
            $byTitle[$productType['title']] = $productType;
        }

        ksort($byTitle);

        return array_values($byTitle);
    }
}
