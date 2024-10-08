<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction;

class Reset extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\AutoAction
{
    public function execute()
    {
        $listingId = $this->getRequest()->getParam('listing_id');
        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->walmartFactory->getCachedObjectLoaded('Listing', $listingId);
        /** @var \Ess\M2ePro\Model\Walmart\Listing $walmartListing */
        $walmartListing = $listing->getChildObject();

        $listingData = [
            'auto_mode' => \Ess\M2ePro\Model\Listing::AUTO_MODE_NONE,
            'auto_global_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_ADD,
            'auto_global_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_website_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'auto_website_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_website_deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE,
        ];

        $listing->addData($listingData)
                ->save();

        $walmartListingData = [
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_AUTO_GLOBAL_ADDING_PRODUCT_TYPE_ID => null,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Listing::COLUMN_AUTO_WEBSITE_ADDING_PRODUCT_TYPE_ID => null,
        ];

        $walmartListing->addData($walmartListingData)
                       ->save();

        foreach ($listing->getAutoCategoriesGroups(true) as $autoCategoryGroup) {
            $autoCategoryGroup->delete();
        }
    }
}
