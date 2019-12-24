<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template\Save
 */
class Save extends Account
{
    public function execute()
    {
        if (!$post = $this->getRequest()->getPost()) {
            return $this->getResult();
        }

        $id = $this->getRequest()->getParam('id');

        // Base prepare
        // ---------------------------------------
        $data = [];

        $keys = [
            'account_id',
            'body'
        ];

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        /** @var \Ess\M2ePro\Model\Ebay\Feedback\Template $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Ebay_Feedback_Template', $id, null, false);

        if ($model === null) {
            /** @var \Ess\M2ePro\Model\Ebay\Feedback\Template $model */
            $model = $this->activeRecordFactory->getObject('Ebay_Feedback_Template');
        }

        $model->addData($data)->save();

        $this->setJsonContent([
            'status' => true
        ]);
        return $this->getResult();
    }
}
