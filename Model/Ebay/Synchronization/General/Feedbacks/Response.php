<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\General\Feedbacks;

class Response extends \Ess\M2ePro\Model\Ebay\Synchronization\General\Feedbacks\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/feedbacks/response/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Response';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 30;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 60;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    protected function intervalIsEnabled()
    {
        return true;
    }

    //########################################

    protected function performActions()
    {
        $feedbacks = $this->getLastUnanswered(5);
        $feedbacks = $this->filterLastAnswered($feedbacks);

        if (count($feedbacks) <= 0) {
            return;
        }

        $iteration = 1;
        $percentsForOneStep = $this->getPercentsInterval() / count($feedbacks);

        foreach ($feedbacks as $feedback) {

            $this->processFeedback($feedback);

            $this->getActualLockItem()->setPercents($this->getPercentsStart() + $iteration * $percentsForOneStep);
            $this->getActualLockItem()->activate();

            $iteration++;
        }
    }

    //########################################

    private function getLastUnanswered($daysAgo = 30)
    {
        $tableAccounts  = $this->activeRecordFactory->getObject('Account')->getResource()->getMainTable();

        $collection = $this->activeRecordFactory->getObject('Ebay\Feedback')->getCollection();
        $collection->getSelect()
            ->join(array('a'=>$tableAccounts),'`a`.`id` = `main_table`.`account_id`',array())
            ->where('`main_table`.`seller_feedback_id` = 0 OR `main_table`.`seller_feedback_id` IS NULL')
            ->where('`main_table`.`buyer_feedback_date` > DATE_SUB(NOW(), INTERVAL ? DAY)',(int)$daysAgo)
            ->order(array('buyer_feedback_date ASC'));

        return $collection->getItems();
    }

    private function filterLastAnswered(array $feedbacks)
    {
        $result = array();

        $responseInterval = (int)$this->getConfigValue($this->getFullSettingsPath(),'attempt_interval');

        foreach ($feedbacks as $feedback) {

            /** @var $feedback \Ess\M2ePro\Model\Ebay\Feedback **/
            $lastResponseAttemptDate = $feedback->getData('last_response_attempt_date');
            $currentGmtDate = $this->getHelper('Data')->getCurrentGmtDate(true);

            if (!is_null($lastResponseAttemptDate) &&
                strtotime($lastResponseAttemptDate) + $responseInterval > $currentGmtDate) {
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

    private function processFeedback(\Ess\M2ePro\Model\Ebay\Feedback $feedback)
    {
        /** @var $feedback \Ess\M2ePro\Model\Ebay\Feedback */
        $account = $feedback->getAccount();

        if ($account->getChildObject()->isFeedbacksAutoResponseCycled()) {
            // Load is needed to get correct feedbacks_last_used_id
            $account = $this->activeRecordFactory->getCachedObjectLoaded(
                'Account', $feedback->getData('account_id')
            );
        }

        if (($body = $this->getResponseBody($account)) == '') {
            return;
        }

        $feedback->sendResponse($body, \Ess\M2ePro\Model\Ebay\Feedback::TYPE_POSITIVE);

        $this->getActualOperationHistory()->appendText('Send Feedback for "'.$feedback->getData('buyer_name').'"');
        $this->getActualOperationHistory()->appendText(
            'His feedback "'.$feedback->getData('buyer_feedback_text').
            '" ('.$feedback->getData('buyer_feedback_type').')'
        );
        $this->getActualOperationHistory()->appendText('Our Feedback "'.$body.'"');

        $this->getActualOperationHistory()->saveBufferString();
    }

    private function getResponseBody(\Ess\M2ePro\Model\Account $account)
    {
        if ($account->getChildObject()->isFeedbacksAutoResponseCycled()) {

            $lastUsedId = 0;
            if ($account->getChildObject()->getFeedbacksLastUsedId() != null) {
                $lastUsedId = (int)$account->getChildObject()->getFeedbacksLastUsedId();
            }

            $feedbackTemplatesIds = $this->activeRecordFactory->getObject('Ebay\Feedback\Template')
                ->getCollection()
                ->addFieldToFilter('account_id', $account->getId())
                ->setOrder('id','ASC')
                ->getAllIds();

            if (!count($feedbackTemplatesIds)) {
                return '';
            }

            $feedbackTemplate = $this->activeRecordFactory->getObject('Ebay\Feedback\Template');

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

            $account->getChildObject()->setData('feedbacks_last_used_id', $feedbackTemplate->getId())->save();

            return $feedbackTemplate->getBody();
        }

        if ($account->getChildObject()->isFeedbacksAutoResponseRandom()) {

            $feedbackTemplatesIds = $this->activeRecordFactory->getObject('Ebay\Feedback\Template')
                ->getCollection()
                ->addFieldToFilter('account_id', $account->getId())
                ->getAllIds();

            if (!count($feedbackTemplatesIds)) {
                return '';
            }

            $index = rand(0, count($feedbackTemplatesIds) - 1);
            $feedbackTemplate = $this->activeRecordFactory->getObject('Ebay\Feedback\Template')->load(
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