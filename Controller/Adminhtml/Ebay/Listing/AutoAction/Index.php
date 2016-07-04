<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\AutoAction;

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
            \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL => 'Ebay\Listing\AutoAction\Mode\GlobalMode',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE => 'Ebay\Listing\AutoAction\Mode\Website',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY => 'Ebay\Listing\AutoAction\Mode\Category',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_NONE => 'Ebay\Listing\AutoAction\Mode'
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