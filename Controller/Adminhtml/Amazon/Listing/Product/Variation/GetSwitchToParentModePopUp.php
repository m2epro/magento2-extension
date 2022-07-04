<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\GetSwitchToParentModePopUp
 */
class GetSwitchToParentModePopUp extends Main
{
    public function execute()
    {
        $block = $this->getLayout()
              ->createBlock(\Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Variation\SwitchToParentPopup::class);

        $this->setAjaxContent($block);

        return $this->getResult();
    }
}
