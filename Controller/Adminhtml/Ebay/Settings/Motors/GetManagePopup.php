<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\Motors;

class GetManagePopup extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Settings
{
    //########################################

    public function execute()
    {
        $popup = $this->createBlock('Ebay\Settings\Tabs\Motors\Manage');

        $this->setAjaxContent($popup);
        return $this->getResult();
    }

    //########################################
}