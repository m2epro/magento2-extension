<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

class GetGrid extends Account
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        if (empty($id)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        $response = $this->createBlock('Ebay\Account\Feedback\Grid')->toHtml();

        $this->setAjaxContent($response);

        return $this->getResult();
    }
}