<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions;

class WebsiteMode
{
    /** @var \Magento\Catalog\Model\Product */
    private $magentoProduct;
    /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Listing\Factory */
    private $autoActionsListingFactory;
    /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\DuplicateProducts */
    private $duplicateProducts;
    /** @var \Magento\Store\Model\StoreManagerInterface */
    private $storeManager;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;

    public function __construct(
        \Magento\Catalog\Model\Product $magentoProduct,
        \Ess\M2ePro\Model\Listing\Auto\Actions\Listing\Factory $autoActionsListingFactory,
        \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\DuplicateProducts $duplicateProducts,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->magentoProduct = $magentoProduct;
        $this->autoActionsListingFactory = $autoActionsListingFactory;
        $this->duplicateProducts = $duplicateProducts;
        $this->storeManager = $storeManager;
        $this->activeRecordFactory = $activeRecordFactory;
    }

    public function synchWithAddedWebsiteId($websiteId)
    {
        if ($websiteId == 0) {
            $storeIds = [\Magento\Store\Model\Store::DEFAULT_STORE_ID];
        } else {
            /** @var \Magento\Store\Model\Website $websiteObject */
            $websiteObject = $this->storeManager->getWebsite($websiteId);
            $storeIds = (array)$websiteObject->getStoreIds();
        }

        if (count($storeIds) <= 0) {
            return;
        }

        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();

        $collection->addFieldToFilter('auto_mode', \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE);
        $collection->addFieldToFilter(
            'auto_website_adding_mode',
            ['neq' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE]
        );
        $collection->addFieldToFilter('store_id', ['in' => $storeIds]);

        foreach ($collection->getItems() as $listing) {
            /** @var \Ess\M2ePro\Model\Listing $listing */

            if (!$listing->isAutoWebsiteAddingAddNotVisibleYes()) {
                if (
                    $this->magentoProduct->getVisibility()
                    == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE
                ) {
                    continue;
                }
            }

            if ($this->duplicateProducts->checkDuplicateListingProduct($listing, $this->magentoProduct)) {
                continue;
            }

            $autoActionListing = $this->autoActionsListingFactory->create($listing);
            $autoActionListing->addProductByWebsiteListing(
                $this->magentoProduct,
                $listing
            );
        }
    }

    public function synchWithDeletedWebsiteId($websiteId)
    {
        /** @var \Magento\Store\Model\Website $websiteObject */
        $websiteObject = $this->storeManager->getWebsite($websiteId);
        $storeIds = (array)$websiteObject->getStoreIds();

        if (count($storeIds) <= 0) {
            return;
        }

        $collection = $this->activeRecordFactory->getObject('Listing')->getCollection();

        $collection->addFieldToFilter('auto_mode', \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE);
        $collection->addFieldToFilter(
            'auto_website_deleting_mode',
            ['neq' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE]
        );

        $collection->addFieldToFilter('store_id', ['in' => $storeIds]);

        /** @var \Ess\M2ePro\Model\Listing $listing */
        foreach ($collection->getItems() as $listing) {
            $autoActionListing = $this->autoActionsListingFactory->create($listing);
            $autoActionListing->deleteProduct(
                $this->magentoProduct,
                $listing->getAutoWebsiteDeletingMode()
            );
        }
    }
}
