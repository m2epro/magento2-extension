<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

class Reset extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    public function execute()
    {
        // ---------------------------------------
        $listingId = $this->getRequest()->getParam('id');
        $listing = $this->amazonFactory->getCachedObjectLoaded('Listing', $listingId);
        // ---------------------------------------

        $data = array(
            'auto_mode' => \Ess\M2ePro\Model\Listing::AUTO_MODE_NONE,
            'auto_global_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'auto_global_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_website_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'auto_website_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_website_deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE,
            'auto_global_adding_description_template_id' => NULL,
            'auto_website_adding_description_template_id' => NULL,
        );

        $listing->addData($data)->save();

        foreach ($listing->getAutoCategoriesGroups(true) as $autoCategoryGroup) {
            $autoCategoryGroup->delete();
        }
    }
}