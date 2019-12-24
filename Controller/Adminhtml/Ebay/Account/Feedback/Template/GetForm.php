<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template\GetForm
 */
class GetForm extends Account
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->activeRecordFactory->getObjectLoaded('Ebay_Feedback_Template', $id, null, false);

        $this->getHelper('Data\GlobalData')->setValue('edit_template', $model);

        $form = $this->createBlock('Ebay_Account_Edit_Tabs_Feedback_Template_Form')->toHtml();

        $title = $model === null ? $this->__('New Feedback Template') : $this->__('Editing Feedback Template');

        $this->setJsonContent([
            'html' => $form,
            'title' => $title
        ]);

        return $this->getResult();
    }
}
