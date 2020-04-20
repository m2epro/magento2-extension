<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Manage\GetSkuPopup
 */
class GetSkuPopup extends Main
{
    public function execute()
    {
        $this->setAjaxContent(
            $this->createBlock('Amazon_Listing_Product_Variation_Manage_Tabs_Settings_SkuPopup_Form')
        );

        return $this->getResult();
    }
}
