<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Feedbacks;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Feedbacks\SendResponse
 */
class SendResponse extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/feedbacks/send_response';

    /**
     * @var int (in seconds)
     */
    protected $interval = 10800;

    const ATTEMPT_INTERVAL = 86400;

    //########################################

    protected function performActions()
    {
        $feedbacks = $this->getLastUnanswered(5);
        $feedbacks = $this->filterLastAnswered($feedbacks);

        if (empty($feedbacks)) {
            return;
        }

        foreach ($feedbacks as $feedback) {
            $this->processFeedback($feedback);
        }
    }

    //########################################

    protected function getLastUnanswered($daysAgo = 30)
    {
        $interval = new \DateTime('now', new \DateTimeZone('UTC'));
        $interval->modify("-{$daysAgo} days");

        $collection = $this->activeRecordFactory->getObject('Ebay\Feedback')->getCollection();
        $collection->getSelect()
            ->join(
                ['a' => $this->activeRecordFactory->getObject('Account')->getResource()->getMainTable()],
                '`a`.`id` = `main_table`.`account_id`',
                []
            )
            ->where('`main_table`.`seller_feedback_id` = 0 OR `main_table`.`seller_feedback_id` IS NULL')
            ->where('`main_table`.`buyer_feedback_date` > ?', $interval->format('Y-m-d H:i:s'))
            ->order(['buyer_feedback_date ASC']);

        return $collection->getItems();
    }

    protected function filterLastAnswered(array $feedbacks)
    {
        $result = [];

        foreach ($feedbacks as $feedback) {

            /** @var $feedback \Ess\M2ePro\Model\Ebay\Feedback **/
            $lastResponseAttemptDate = $feedback->getData('last_response_attempt_date');
            $currentGmtDate = $this->getHelper('Data')->getCurrentGmtDate(true);

            if ($lastResponseAttemptDate !== null &&
                strtotime($lastResponseAttemptDate) + self::ATTEMPT_INTERVAL > $currentGmtDate) {
                continue;
            }

            $ebayAccount = $feedback->getEbayAccount();

            if (!$ebayAccount->isFeedbacksReceive()) {
                continue;
            }

            if ($ebayAccount->isFeedbacksAutoResponseDisabled()) {
                continue;
            }

            if ($ebayAccount->isFeedbacksAutoResponseOnlyPositive() && !$feedback->isPositive()) {
                continue;
            }

            if (!$ebayAccount->hasFeedbackTemplate()) {
                continue;
            }

            $result[] = $feedback;
        }

        return $result;
    }

    // ---------------------------------------

    protected function processFeedback(\Ess\M2ePro\Model\Ebay\Feedback $feedback)
    {
        /** @var $feedback \Ess\M2ePro\Model\Ebay\Feedback */
        $account = $feedback->getAccount();

        if ($account->getChildObject()->isFeedbacksAutoResponseCycled()) {
            // Load is needed to get correct feedbacks_last_used_id
            $account = $this->parentFactory->getCachedObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'Account',
                $feedback->getData('account_id')
            );
        }

        if (($body = $this->getResponseBody($account)) == '') {
            return;
        }

        $feedback->sendResponse($body, \Ess\M2ePro\Model\Ebay\Feedback::TYPE_POSITIVE);

        $this->getOperationHistory()->appendText('Send Feedback for "'.$feedback->getData('buyer_name').'"');
        $this->getOperationHistory()->appendText(
            'His feedback "'.$feedback->getData('buyer_feedback_text').
            '" ('.$feedback->getData('buyer_feedback_type').')'
        );
        $this->getOperationHistory()->appendText('Our Feedback "'.$body.'"');

        $this->getOperationHistory()->saveBufferString();
    }

    protected function getResponseBody(\Ess\M2ePro\Model\Account $account)
    {
        if ($account->getChildObject()->isFeedbacksAutoResponseCycled()) {
            $lastUsedId = 0;
            if ($account->getChildObject()->getFeedbacksLastUsedId() != null) {
                $lastUsedId = (int)$account->getChildObject()->getFeedbacksLastUsedId();
            }

            $feedbackTemplatesIds = $this->activeRecordFactory->getObject('Ebay_Feedback_Template')->getCollection()
                ->addFieldToFilter('account_id', $account->getId())
                ->setOrder('id', 'ASC')
                ->getAllIds();

            if (!count($feedbackTemplatesIds)) {
                return '';
            }

            $feedbackTemplate = $this->activeRecordFactory->getObject('Ebay_Feedback_Template');

            if (max($feedbackTemplatesIds) > $lastUsedId) {
                foreach ($feedbackTemplatesIds as $templateId) {
                    if ($templateId <= $lastUsedId) {
                        continue;
                    }

                    $feedbackTemplate->load($templateId);
                    break;
                }
            } else {
                $feedbackTemplate->load(min($feedbackTemplatesIds));
            }

            if (!$feedbackTemplate->getId()) {
                return '';
            }

            $account->setData('feedbacks_last_used_id', $feedbackTemplate->getId())->save();

            return $feedbackTemplate->getBody();
        }

        if ($account->getChildObject()->isFeedbacksAutoResponseRandom()) {
            $feedbackTemplatesIds = $this->activeRecordFactory->getObject('Ebay_Feedback_Template')->getCollection()
                ->addFieldToFilter('account_id', $account->getId())
                ->getAllIds();

            if (!count($feedbackTemplatesIds)) {
                return '';
            }

            $index = rand(0, count($feedbackTemplatesIds) - 1);
            $feedbackTemplate = $this->activeRecordFactory->getObjectLoaded(
                'Ebay_Feedback_Template',
                $feedbackTemplatesIds[$index]
            );

            if (!$feedbackTemplate->getId()) {
                return '';
            }

            return $feedbackTemplate->getBody();
        }

        return '';
    }

    //########################################
}
