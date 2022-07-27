<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Settings\Motors;

class GetManagePopup extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Settings
{
    public function execute()
    {
        $popup = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs\Motors\Manage::class);

        $this->setAjaxContent($popup);
        return $this->getResult();
    }
}
