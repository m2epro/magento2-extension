<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction\Reset
 */
class Reset extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Model\Listing $listing */
        $listing = $this->amazonFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );

        $listingData = [
            'auto_mode' => \Ess\M2ePro\Model\Listing::AUTO_MODE_NONE,
            'auto_global_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_ADD,
            'auto_global_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_website_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'auto_website_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_website_deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE,
        ];

        $listing->addData($listingData)->save();

        /** @var \Ess\M2ePro\Model\Amazon\Listing $amazonListing */
        $amazonListing = $listing->getChildObject();

        $amazonListingData = [
            'auto_global_adding_product_type_template_id' => null,
            'auto_website_adding_product_type_template_id' => null,
        ];

        $amazonListing->addData($amazonListingData)->save();

        foreach ($listing->getAutoCategoriesGroups(true) as $autoCategoryGroup) {
            $autoCategoryGroup->delete();
        }
    }
}
