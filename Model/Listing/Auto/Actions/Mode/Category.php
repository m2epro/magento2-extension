<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions\Mode;

class Category extends AbstractMode
{
    private $cacheLoadedListings = array();
    private $cacheAutoCategoriesByCategoryId = array();

    //########################################

    public function synchWithAddedCategoryId($categoryId, $websiteId)
    {
        foreach ($this->getAutoCategoriesByCategory($categoryId) as $autoCategory) {

            /** @var $autoCategory \Ess\M2ePro\Model\Listing\Auto\Category */

            if ($autoCategory->getGroup()->isAddingModeNone()) {
                continue;
            }

            $listing = $this->getLoadedListing($autoCategory->getGroup()->getListingId());

            if ((int)$listing->getData('store_website_id') != $websiteId) {
                continue;
            }

            if (!$listing->isAutoModeCategory()) {
                continue;
            }

            if (!$autoCategory->getGroup()->isAddingAddNotVisibleYes()) {
                if ($this->getProduct()->getVisibility()
                    == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE) {
                    continue;
                }
            }

            $this->getListingObject($listing)->addProductByCategoryGroup(
                $this->getProduct(), $autoCategory->getGroup()
            );
        }
    }

    public function synchWithDeletedCategoryId($categoryId, $websiteId)
    {
        foreach ($this->getAutoCategoriesByCategory($categoryId) as $autoCategory) {

            /** @var $autoCategory \Ess\M2ePro\Model\Listing\Auto\Category */

            if ($autoCategory->getGroup()->isDeletingModeNone()) {
                continue;
            }

            /** @var $listing \Ess\M2ePro\Model\Listing */

            $listing = $this->getLoadedListing($autoCategory->getGroup()->getListingId());

            if ((int)$listing->getData('store_website_id') != $websiteId) {
                continue;
            }

            if (!$listing->isAutoModeCategory()) {
                continue;
            }

            $this->getListingObject($listing)->deleteProduct(
                $this->getProduct(),
                $autoCategory->getGroup()->getDeletingMode()
            );
        }
    }

    //########################################

    private function getLoadedListing($listing)
    {
        if ($listing instanceof \Ess\M2ePro\Model\Listing) {
            return $listing;
        }

        $listingId = (int)$listing;

        if (isset($this->cacheLoadedListings[$listingId])) {
            return $this->cacheLoadedListings[$listingId];
        }

        /** @var $listing \Ess\M2ePro\Model\Listing */
        $listing = $this->activeRecordFactory->getCachedObjectLoaded('Listing', $listingId);

        /** @var $listingStoreObject \Magento\Store\Model\Store */
        $listingStoreObject = $this->storeManager->getStore($listing->getStoreId());
        $listing->setData('store_website_id',$listingStoreObject->getWebsite()->getId());

        return $this->cacheLoadedListings[$listingId] = $listing;
    }

    private function getAutoCategoriesByCategory($categoryId)
    {
        if (isset($this->cacheAutoCategoriesByCategoryId[$categoryId])) {
            return $this->cacheAutoCategoriesByCategoryId[$categoryId];
        }

        return $this->cacheAutoCategoriesByCategoryId[$categoryId] =
            $this->activeRecordFactory->getObject('Listing\Auto\Category')
                ->getCollection()
                ->addFieldToFilter('category_id', $categoryId)
                ->getItems();
    }

    //########################################
}