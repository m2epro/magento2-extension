<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template\Delete
 */
class Delete extends Account
{
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        /** @var \Ess\M2ePro\Model\Ebay\Feedback\Template $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Ebay_Feedback_Template', $id, null, false);

        if ($model !== null) {
            $model->delete();
        }

        $this->setJsonContent([
            'status' => true
        ]);
        return $this->getResult();
    }
}
