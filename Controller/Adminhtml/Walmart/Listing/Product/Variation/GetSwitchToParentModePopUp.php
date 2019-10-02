<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;

/**
 * Class GetSwitchToParentModePopUp
 * @package Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation
 */
class GetSwitchToParentModePopUp extends Main
{
    public function execute()
    {
        $block = $this->createBlock('Walmart_Listing_Product_Variation_SwitchToParentPopup');

        $this->setAjaxContent($block);

        return $this->getResult();
    }
}
