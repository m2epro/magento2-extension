<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

class Delete extends Account
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        /** @var \Ess\M2ePro\Model\Ebay\Feedback\Template $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Ebay\Feedback\Template', $id, NULL, false);

        if (!is_null($model)) {
            $model->delete();
        }

        $this->setJsonContent([
            'status' => true
        ]);
        return $this->getResult();
    }
}