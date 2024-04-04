<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Listing\Auto\Actions;

class CategoryProductRelationProcessor
{
    /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Factory */
    private $listingAutoActionsModeFactory;
    /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\CategoryProductRelationProcessor\Repository */
    private $repository;
    /** @var \Magento\Store\Model\StoreManager */
    private $storeManager;

    public function __construct(
        CategoryProductRelationProcessor\Repository $repository,
        \Magento\Store\Model\StoreManager $storeManager,
        \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Factory $listingAutoActionsModeFactory
    ) {
        $this->repository = $repository;
        $this->listingAutoActionsModeFactory = $listingAutoActionsModeFactory;
        $this->storeManager = $storeManager;
    }

    public function isNeedProcess(int $categoryId): bool
    {
        return $this->repository->isExistsAutoActionsForCategory($categoryId);
    }

    /**
     * @param int[] $postedProductsIds
     * @param int[] $changedProductsIds
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(
        int $categoryId,
        int $websiteId,
        array $postedProductsIds,
        array $changedProductsIds
    ): void {
        $websitesProductsIds = $this->getGetWebsitesProductsIds($websiteId, $changedProductsIds);

        $allStoreIds = [];
        foreach (array_keys($websitesProductsIds) as $websiteId) {
            $websiteStoreIds = $this->storeManager
                ->getWebsite($websiteId)
                ->getStoreIds();
            foreach ($websiteStoreIds as $storeId) {
                $allStoreIds[] = $storeId;
            }
        }

        $listingIdsWithEnabledRuleAdded = $this->repository->findListingIdsWithEnabledRuleAddedToCategory(
            $categoryId,
            $allStoreIds
        );
        $listingIdsWithEnabledRuleRemoved = $this->repository->findListingIdsWithEnabledRuleRemovedFromCategory(
            $categoryId,
            $allStoreIds
        );

        if (
            empty($listingIdsWithEnabledRuleAdded)
            && empty($listingIdsWithEnabledRuleRemoved)
        ) {
            return;
        }

        $resolvedWebsitesProductsIds = $this->resolveWebsitesProductsIds(
            $websitesProductsIds,
            $postedProductsIds
        );
        $websitesProductsIdsForAdding = $resolvedWebsitesProductsIds['forAdding'];
        $websitesProductsIdsForRemove = $resolvedWebsitesProductsIds['forRemove'];

        if (
            !empty($websitesProductsIdsForAdding)
            && !empty($listingIdsWithEnabledRuleAdded)
        ) {
            foreach ($websitesProductsIdsForAdding as $websiteId => $productIds) {
                $this->processAddProductsToListings(
                    $categoryId,
                    $websiteId,
                    $productIds,
                    $listingIdsWithEnabledRuleAdded
                );
            }
        }

        if (
            !empty($websitesProductsIdsForRemove)
            && !empty($listingIdsWithEnabledRuleRemoved)
        ) {
            foreach ($websitesProductsIdsForRemove as $websiteId => $productIds) {
                $this->processRemoveProductsFromListings(
                    $categoryId,
                    $websiteId,
                    $productIds,
                    $listingIdsWithEnabledRuleRemoved
                );
            }
        }
    }

    /**
     * @return array<int, int[]>
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getGetWebsitesProductsIds(int $websiteId, array $productIds): array
    {
        $websitesProductsIds = [
            // Website for default store view
            0 => $productIds,
        ];

        if ($websiteId !== 0) {
            $websitesProductsIds[$websiteId] = $productIds;

            return $websitesProductsIds;
        }

        $catalogWebsiteProductIds = $this->repository->retrieveCatalogWebsiteProductsIds($productIds);
        foreach ($catalogWebsiteProductIds as $websiteId => $productIds) {
            $websitesProductsIds[$websiteId] = $productIds;
        }

        return $websitesProductsIds;
    }

    /**
     * @param array<int, int[]> $websitesProductsIds
     * @param int[] $postedProductsIds
     *
     * @return array{forAdding: array<int, int[]>, forRemove: array<int, int[]>}
     */
    private function resolveWebsitesProductsIds(array $websitesProductsIds, array $postedProductsIds): array
    {
        $websitesProductsIdsForAdding = [];
        $websitesProductsIdsForRemove = [];

        foreach ($websitesProductsIds as $websiteId => $productsIds) {
            foreach ($productsIds as $productId) {
                if (in_array($productId, $postedProductsIds)) {
                    $websitesProductsIdsForAdding[$websiteId][] = $productId;
                } else {
                    $websitesProductsIdsForRemove[$websiteId][] = $productId;
                }
            }
        }

        return [
            'forAdding' => $websitesProductsIdsForAdding,
            'forRemove' => $websitesProductsIdsForRemove,
        ];
    }

    private function processAddProductsToListings(
        int $categoryId,
        int $websiteId,
        array $productIds,
        array $listingIds
    ): void {
        $products = $this->repository->findProductsThatNotInListings($productIds, $listingIds);
        foreach ($products as $product) {
            $categoryAutoAction = $this->listingAutoActionsModeFactory->createCategoryMode($product);
            $categoryAutoAction->synchWithAddedCategoryId($websiteId, [$categoryId]);
        }
    }

    private function processRemoveProductsFromListings(
        int $categoryId,
        int $websiteId,
        array $productIds,
        array $listingIds
    ): void {
        $products = $this->repository->findProductsThatInListings($productIds, $listingIds);
        foreach ($products as $product) {
            $categoryAutoAction = $this->listingAutoActionsModeFactory->createCategoryMode($product);
            $categoryAutoAction->synchWithDeletedCategoryId($websiteId, [$categoryId]);
        }
    }
}
