<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\GetPopup
 */
class GetPopup extends Account
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $account = $this->ebayFactory->getObjectLoaded('Account', $id, null, false);

        if (empty($id) || $account === null) {
            $this->setAjaxContent('Account not found.', false);
            return $this->getResult();
        }

        $this->setJsonContent([
            'html' => $this->createBlock('Ebay_Account_Feedback')->toHtml(),
            'title' => $this->__('Feedback for account "%account_title%"', $account->getTitle())
        ]);

        return $this->getResult();
    }
}
