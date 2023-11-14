<?php

namespace Ess\M2ePro\Model\Amazon\ProductType;

use Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByCriteria\Response;

class CategoryFinder
{
    private const CACHE_LIFE_TIME = 3600;

    /** @var \Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByCriteria\Processor */
    private $connectProcessor;
    /** @var \Ess\M2ePro\Helper\Component\Amazon\ProductType */
    private $repository;
    /** @var \Ess\M2ePro\Model\MarketplaceFactory */
    private $marketplaceFactory;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $cachePermanent;
    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent,
        \Ess\M2ePro\Model\MarketplaceFactory $marketplaceFactory,
        \Ess\M2ePro\Model\Amazon\ProductType\CategoryFinder\Repository $repository,
        \Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByCriteria\Processor $connectProcessor
    ) {
        $this->helperData = $helperData;
        $this->cachePermanent = $cachePermanent;
        $this->marketplaceFactory = $marketplaceFactory;
        $this->repository = $repository;
        $this->connectProcessor = $connectProcessor;
    }

    /**
     * @param int $marketplaceId
     * @param string[] $criteria
     *
     * @return CategoryFinder\Category[]
     */
    public function find(int $marketplaceId, array $criteria): array
    {
        $marketplace = $this->marketplaceFactory->create();
        $marketplace->load($marketplaceId);

        if (!$marketplace->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Invalid marketplace id');
        }

        $marketplaceNativeId = $marketplace->getNativeId();

        $cachedCategories = $this->getCategoriesFromCache($marketplaceNativeId, $criteria);

        if ($cachedCategories === false) {
            $response = $this->fetchCategoriesFromServer($marketplaceNativeId, $criteria);
            $this->setCategoriesToCache($marketplaceNativeId, $criteria, $response);
        } else {
            $response = $this->buildResponseFromCachedData($cachedCategories);
        }

        $availableProductTypes = $this->getAvailableProductTypes($marketplaceId, $response);

        $categories = [];
        foreach ($response->getCategories() as $category) {
            $categoryItem = new CategoryFinder\Category(
                $category['name'],
                $category['isLeaf']
            );

            foreach ($category['nicksOfProductTypes'] as $productTypeNick) {
                if (isset($availableProductTypes[$productTypeNick])) {
                    $productType = new CategoryFinder\ProductType(
                        $availableProductTypes[$productTypeNick]['title'],
                        $availableProductTypes[$productTypeNick]['nick'],
                        $availableProductTypes[$productTypeNick]['templateId']
                    );
                    $categoryItem->addProductType($productType);
                }
            }

            $categories[] = $categoryItem;
        }

        return $categories;
    }

    /**
     * @param int $marketplaceId
     * @param \Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByCriteria\Response $response
     *
     * @return array<string, array{nick:string, title:string, templateId: int|null}>
     */
    private function getAvailableProductTypes(int $marketplaceId, Response $response): array
    {
        $nicks = [];
        foreach ($response->getCategories() as $category) {
            foreach ($category['nicksOfProductTypes'] as $productTypeNick) {
                $nicks[] = $productTypeNick;
            }
        }

        return $this->repository->getAvailableProductTypes(
            $marketplaceId,
            array_unique($nicks)
        );
    }

    private function fetchCategoriesFromServer(int $marketplaceNativeId, array $criteria): Response
    {
        $request = new \Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByCriteria\Request(
            $marketplaceNativeId,
            $criteria
        );

        return $this->connectProcessor->process($request);
    }

    private function buildResponseFromCachedData(array $cachedCategories): Response
    {
        $response = new Response();

        foreach ($cachedCategories as $category) {
            $response->addCategory(
                $category['name'],
                $category['isLeaf'],
                $category['nicksOfProductTypes']
            );
        }

        return $response;
    }

    private function getCategoriesFromCache(int $marketplaceId, array $criteria)
    {
        $cacheKey = $this->getCacheKey($marketplaceId, $criteria);
        $cachedCategories = $this->cachePermanent->getValue($cacheKey);

        if ($cachedCategories !== null) {
            return \Ess\M2ePro\Helper\Json::decode($cachedCategories);
        }

        return false;
    }

    private function getCacheKey(int $marketplaceId, array $criteria): string
    {
        return $marketplaceId . '_' . $this->helperData->md5String(\Ess\M2ePro\Helper\Json::encode($criteria));
    }

    private function setCategoriesToCache(int $marketplaceNativeId, array $criteria, Response $categories): void
    {
        $cacheKey = $this->getCacheKey($marketplaceNativeId, $criteria);

        $this->cachePermanent->setValue(
            $cacheKey,
            \Ess\M2ePro\Helper\Json::encode($categories->getCategories()),
            [],
            self::CACHE_LIFE_TIME
        );
    }
}
