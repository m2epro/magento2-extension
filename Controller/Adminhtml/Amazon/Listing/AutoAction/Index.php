<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AutoAction
{
    public function execute()
    {
        // ---------------------------------------
        $listingId = $this->getRequest()->getParam('id');
        $autoMode  = $this->getRequest()->getParam('auto_mode');
        $listing   = $this->amazonFactory->getCachedObjectLoaded('Listing', $listingId);

        $this->getHelper('Data\GlobalData')->setValue('listing', $listing);
        // ---------------------------------------

        if (empty($autoMode)) {
            $autoMode = $listing->getAutoMode();
        }

        $autoModes = [
            \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL => 'Amazon\Listing\AutoAction\Mode\GlobalMode',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE => 'Amazon\Listing\AutoAction\Mode\Website',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY => 'Amazon\Listing\AutoAction\Mode\Category',
            \Ess\M2ePro\Model\Listing::AUTO_MODE_NONE => 'Amazon\Listing\AutoAction\Mode'
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