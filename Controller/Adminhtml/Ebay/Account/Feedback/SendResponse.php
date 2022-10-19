<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\Feedback;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Account;

class SendResponse extends Account
{
    /** @var \Ess\M2ePro\Model\Ebay\Feedback\Manager */
    private $ebayFeedbackManager;

    /**
     * @param \Ess\M2ePro\Model\Ebay\Feedback\Manager $ebayFeedbackManager
     * @param \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate
     * @param \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Model\Ebay\Feedback\Manager $ebayFeedbackManager,
        \Ess\M2ePro\Model\Ebay\Account\Store\Category\Update $storeCategoryUpdate,
        \Ess\M2ePro\Helper\Component\Ebay\Category\Store $componentEbayCategoryStore,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($storeCategoryUpdate, $componentEbayCategoryStore, $ebayFactory, $context);
        $this->ebayFeedbackManager = $ebayFeedbackManager;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
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

        $result = $this->ebayFeedbackManager->sendResponse(
            $feedback,
            $feedbackText,
            \Ess\M2ePro\Model\Ebay\Feedback::TYPE_POSITIVE
        );

        $this->setJsonContent([
            'type' => $result ? 'success' : 'error',
            'text' => $result ?
                $this->__('Feedback has been sent.') :
                $this->__('Feedback was not sent.')
        ]);

        return $this->getResult();
    }
}
