<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\GetSendResponseForm
 */
class GetSendResponseForm extends Account
{
    public function execute()
    {
        $feedbackId = $this->getRequest()->getParam('feedback_Id');
        /** @var \Ess\M2ePro\Model\Ebay\Feedback $feedback */
        $feedback = $this->activeRecordFactory->getObjectLoaded('Ebay\Feedback', $feedbackId, null, false);

        if (empty($feedbackId) || $feedback === null) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        $this->getHelper('Data\GlobalData')->setValue('feedback', $feedback);

        $form = $this->createBlock('Ebay_Account_Feedback_SendResponseForm')->toHtml();

        $title = $this->__('Send Response For Feedback (Item ID: %item_id%)', $feedback->getEbayItemId());

        $this->setJsonContent([
            'html' => $form,
            'title' => $title
        ]);

        return $this->getResult();
    }
}
