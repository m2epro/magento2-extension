<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

class TemplateCheck extends Account
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->ebayFactory->getCachedObjectLoaded('Account',$id);

        $this->setJsonContent([
            'ok' => (bool)$model->getChildObject()->hasFeedbackTemplate()
        ]);

        return $this->getResult();
    }
}