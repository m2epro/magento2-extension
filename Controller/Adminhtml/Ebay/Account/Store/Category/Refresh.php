<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Store\Category;

class Refresh extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    public function execute()
    {
        $accountId = (int)$this->getRequest()->getParam('account_id');

        $account = $this->ebayFactory->getObjectLoaded('Account', $accountId)->getChildObject();

        $this->storeCategoryUpdate->process($account);

        return $this->getResult();
    }

    //########################################
}
