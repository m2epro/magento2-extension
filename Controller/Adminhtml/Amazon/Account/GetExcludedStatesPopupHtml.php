<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Account\GetExcludedStatesPopupHtml
 */
class GetExcludedStatesPopupHtml extends Account
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs\Order\ExcludedStates $block */
        $block = $this->createBlock('Amazon_Account_Edit_Tabs_Order_ExcludedStates');
        $block->setData('selected_states', explode(',', $this->getRequest()->getParam('selected_states')));

        $this->setAjaxContent($block);
        return $this->getResult();
    }
}
