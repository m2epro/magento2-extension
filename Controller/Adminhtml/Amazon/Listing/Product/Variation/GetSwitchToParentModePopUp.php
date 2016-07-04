<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class GetSwitchToParentModePopUp extends Main
{
    public function execute()
    {
        $block = $this->createBlock('Amazon\Listing\Product\Variation\SwitchToParentPopup');

        $this->setAjaxContent($block);

        return $this->getResult();
    }
}