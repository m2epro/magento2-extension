<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction
{
    public function execute()
    {
        // ---------------------------------------
        $listingId = $this->getRequest()->getParam('id');
        $autoMode  = $this->getRequest()->getParam('auto_mode');
        $listing   = $this->ebayFactory->getCachedObjectLoaded('Listing', $listingId);

        $this->getHelper('Data\GlobalData')->setValue('listing', $listing);
        // ---------------------------------------

        if (empty($autoMode)) {
            $autoMode = $listing->getAutoMode();
        }

        $autoModes = [
            \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL => 'Ebay_Listing_AutoAction_Mode_GlobalMode',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE => 'Ebay_Listing_AutoAction_Mode_Website',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY => 'Ebay_Listing_AutoAction_Mode_Category',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_NONE => 'Ebay_Listing_AutoAction_Mode'
        ];

        if (isset($autoModes[$autoMode])) {
            $blockName = $autoModes[$autoMode];
        } else {
            $blockName = $autoModes[\Ess\M2ePro\Model\Listing::AUTO_MODE_NONE];
        }

        $this->setJsonContent([
            'mode' => $autoMode,
            'html' => $this->createBlock($blockName)->toHtml()
        ]);
        return $this->getResult();
    }
}
