<?php
/**
 * Created by PhpStorm.
 * User: HardRock
 * Date: 14.03.2016
 * Time: 17:23
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing;

class SaveListingAdditionalData extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    public function execute()
    {
        $listingId = $this->getRequest()->getParam('id');
        $paramName = $this->getRequest()->getParam('param_name');
        $paramValue = $this->getRequest()->getParam('param_value');

        if (empty($listingId) || empty($paramName) || empty($paramValue)) {
            return $this->getResponse()->setBody('You should provide correct parameters.');
        }

        $listing = $this->activeRecordFactory->getObjectLoaded('Listing', $listingId);

        $listing->setSetting('additional_data', $paramName, $paramValue);
        $listing->save();

        $this->setAjaxContent(0, false);

        return $this->getResult();
    }
}