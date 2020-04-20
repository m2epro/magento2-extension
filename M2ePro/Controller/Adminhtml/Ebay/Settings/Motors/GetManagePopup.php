<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\Motors;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\Motors\GetManagePopup
 */
class GetManagePopup extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Settings
{
    //########################################

    public function execute()
    {
        $popup = $this->createBlock('Ebay_Settings_Tabs_Motors_Manage');

        $this->setAjaxContent($popup);
        return $this->getResult();
    }

    //########################################
}
