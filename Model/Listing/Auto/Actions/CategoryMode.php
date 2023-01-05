<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions;

use Magento\Catalog\Model\Product\Visibility as ProductVisibility;

class CategoryMode
{
    /** @var \Magento\Catalog\Model\Product */
    private $magentoProduct;
    /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Listing\Factory */
    private $autoActionsListingFactory;
    /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\DuplicateProducts */
    private $duplicateProducts;
    /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Category\Repository */
    private $repository;

    public function __construct(
        \Magento\Catalog\Model\Product $magentoProduct,
        \Ess\M2ePro\Model\Listing\Auto\Actions\Listing\Factory $autoActionsListingFactory,
        \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\DuplicateProducts $duplicateProducts,
        \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Category\Repository $repository
    ) {
        $this->magentoProduct = $magentoProduct;
        $this->autoActionsListingFactory = $autoActionsListingFactory;
        $this->duplicateProducts = $duplicateProducts;
        $this->repository = $repository;
    }

    /**
     * @param int $websiteId
     * @param array $idsOfAddedCategories
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function synchWithAddedCategoryId(int $websiteId, array $idsOfAddedCategories): void
    {
        $groupSet = $this->repository->getGroupSet($idsOfAddedCategories);

        if ($groupSet->isEmpty()) {
            return;
        }

        $groupSet = $this->filterGroupsFromSetByWebsite($websiteId, $groupSet);
        $groupSet = $this->excludeGroupsWithDuplicateProductsFromSet($groupSet);

        foreach ($groupSet->getGroups() as $group) {
            $listing = $this->repository->getLoadedListing($group->getListingId());
            $autoCategoryGroups = $this->repository->getAutoCategoryGroups($group);
            foreach ($autoCategoryGroups as $autoCategoryGroup) {
                if ($autoCategoryGroup->isAddingModeNone()) {
                    continue;
                }

                if (
                    !$autoCategoryGroup->isAddingAddNotVisibleYes()
                    && $this->magentoProduct->getVisibility() == ProductVisibility::VISIBILITY_NOT_VISIBLE
                ) {
                    continue;
                }

                $autoActionListing = $this->autoActionsListingFactory->create($listing);
                $autoActionListing->addProductByCategoryGroup(
                    $this->magentoProduct,
                    $autoCategoryGroup
                );
            }
        }
    }

    /**
     * @param Mode\Category\GroupSet $collection
     *
     * @return Mode\Category\GroupSet
     */
    private function excludeGroupsWithDuplicateProductsFromSet(
        Mode\Category\GroupSet $collection
    ): Mode\Category\GroupSet {
        return $collection->filter(function (Mode\Category\Group $group) {
            $listing = $this->repository->getLoadedListing($group->getListingId());

            return !$this->duplicateProducts->checkDuplicateListingProduct($listing, $this->magentoProduct);
        });
    }

    /**
     * @param int $websiteId
     * @param array $idsOfRemovedCategories
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function synchWithDeletedCategoryId(int $websiteId, array $idsOfRemovedCategories): void
    {
        $groupSet = $this->repository->getGroupSet(
            $idsOfRemovedCategories,
            $this->magentoProduct
        );

        if ($groupSet->isEmpty()) {
            return;
        }

        $idsOfCategoriesThatStayInMagentoProduct = $this->magentoProduct->getCategoryIds();
        $groupSet = $groupSet->excludeGroupsThatContainsCategoryIds(
            $idsOfCategoriesThatStayInMagentoProduct
        );

        $groupSet = $this->filterGroupsFromSetByWebsite($websiteId, $groupSet);

        foreach ($groupSet->getGroups() as $group) {
            $listing = $this->repository->getLoadedListing($group->getListingId());
            $autoCategoryGroups = $this->repository->getAutoCategoryGroups($group);
            foreach ($autoCategoryGroups as $autoCategoryGroup) {
                if ($autoCategoryGroup->isDeletingModeNone()) {
                    continue;
                }
                $autoActionListing = $this->autoActionsListingFactory->create($listing);
                $autoActionListing->deleteProduct($this->magentoProduct, $autoCategoryGroup->getDeletingMode());
            }
        }
    }

    /**
     * @param int $websiteId
     * @param Mode\Category\GroupSet $groupSet
     *
     * @return Mode\Category\GroupSet
     */
    private function filterGroupsFromSetByWebsite(
        int $websiteId,
        Mode\Category\GroupSet $groupSet
    ): Mode\Category\GroupSet {
        return $groupSet->filter(function (Mode\Category\Group $group) use ($websiteId) {
            $listingId = $group->getListingId();
            $listing = $this->repository->getLoadedListing($listingId);
            $storeWebsiteId = $this->repository->getStoreWebsiteIdByListingId($listingId);

            return $storeWebsiteId === $websiteId && $listing->isAutoModeCategory();
        });
    }
}
