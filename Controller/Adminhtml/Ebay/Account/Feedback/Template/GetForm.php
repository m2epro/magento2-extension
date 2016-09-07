<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

class GetForm extends Account
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->activeRecordFactory->getObjectLoaded('Ebay\Feedback\Template', $id, NULL, false);

        $this->getHelper('Data\GlobalData')->setValue('edit_template', $model);

        $form = $this->createBlock('Ebay\Account\Edit\Tabs\Feedback\Template\Form')->toHtml();

        $title = is_null($model) ? $this->__('New Feedback Template') : $this->__('Editing Feedback Template');

        $this->setJsonContent([
            'html' => $form,
            'title' => $title
        ]);

        return $this->getResult();
    }
}