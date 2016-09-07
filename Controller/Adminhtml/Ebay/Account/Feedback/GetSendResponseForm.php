<?php

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

class GetSendResponseForm extends Account
{
    public function execute()
    {
        $feedbackId = $this->getRequest()->getParam('feedback_Id');
        /** @var \Ess\M2ePro\Model\Ebay\Feedback $feedback */
        $feedback = $this->activeRecordFactory->getObjectLoaded('Ebay\Feedback', $feedbackId, NULL, false);

        if (empty($feedbackId) || is_null($feedback)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        $this->getHelper('Data\GlobalData')->setValue('feedback', $feedback);

        $form = $this->createBlock('Ebay\Account\Feedback\SendResponseForm')->toHtml();

        $title = $this->__('Send Response For Feedback (Item ID: %item_id%)', $feedback->getEbayItemId());

        $this->setJsonContent([
            'html' => $form,
            'title' => $title
        ]);

        return $this->getResult();
    }
}