<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\TemplateCheck
 */
class TemplateCheck extends Account
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->ebayFactory->getCachedObjectLoaded('Account', $id);

        $this->setJsonContent([
            'ok' => (bool)$model->getChildObject()->hasFeedbackTemplate()
        ]);

        return $this->getResult();
    }
}
