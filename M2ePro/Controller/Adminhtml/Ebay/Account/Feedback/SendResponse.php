<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback\SendResponse
 */
class SendResponse extends Account
{
    public function execute()
    {
        $feedbackId = $this->getRequest()->getParam('feedback_id');
        $feedbackText = $this->getRequest()->getParam('feedback_text');

        /** @var \Ess\M2ePro\Model\Ebay\Feedback $feedback */
        $feedback = $this->activeRecordFactory->getObjectLoaded('Ebay\Feedback', $feedbackId, null, false);

        if (empty($feedbackId) || $feedback === null) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        $feedbackText = strip_tags($feedbackText);

        $result = $feedback->sendResponse($feedbackText, \Ess\M2ePro\Model\Ebay\Feedback::TYPE_POSITIVE);

        $this->setJsonContent([
            'type' => $result ? 'success' : 'error',
            'text' => $result ?
                $this->__('Feedback has been successfully sent.') :
                $this->__('Feedback was not sent.')
        ]);

        return $this->getResult();
    }
}
