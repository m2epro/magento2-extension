<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\Template;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

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
        $data = array();

        $keys = array(
            'account_id',
            'body'
        );

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        /** @var \Ess\M2ePro\Model\Ebay\Feedback\Template $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Ebay\Feedback\Template', $id, NULL, false);

        if (is_null($model)) {
            /** @var \Ess\M2ePro\Model\Ebay\Feedback\Template $model */
            $model = $this->activeRecordFactory->getObject('Ebay\Feedback\Template');
        }

        $model->addData($data)->save();

        $this->setJsonContent([
            'status' => true
        ]);
        return $this->getResult();
    }
}