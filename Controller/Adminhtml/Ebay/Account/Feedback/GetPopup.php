<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

class GetPopup extends Account
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $account = $this->ebayFactory->getObjectLoaded('Account', $id, NULL, false);

        if (empty($id) || is_null($account)) {
            $this->setAjaxContent('Account not found.', false);
            return $this->getResult();
        }

        $this->setJsonContent([
            'html' => $this->createBlock('Ebay\Account\Feedback')->toHtml(),
            'title' => $this->__('Feedback for account "%account_title%"', $account->getTitle())
        ]);

        return $this->getResult();
    }
}