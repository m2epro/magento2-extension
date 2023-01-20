<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Feedbacks;

use Ess\M2ePro\Model\ResourceModel\Ebay\Feedback\Template\CollectionFactory as EbayFeedbackTemplateCollectionFactory;

class SendResponse extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    public const NICK = 'ebay/feedbacks/send_response';

    private const ATTEMPT_INTERVAL = 86400;

    /** @var int (in seconds) */
    protected $interval = 10800;
    /** @var \Ess\M2ePro\Model\Ebay\Feedback\Manager */
    private $ebayFeedbackManager;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Account\CollectionFactory */
    private $ebayAccountCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Feedback\CollectionFactory */
    private $ebayFeedbackCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Feedback\Template\CollectionFactory */
    private $ebayFeedbackTemplateCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Account */
    private $ebayAccountResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Feedback */
    private $ebayFeedbackResource;

    /**
     * @param \Ess\M2ePro\Model\Ebay\Feedback\Manager $ebayFeedbackManager
     * @param \Ess\M2ePro\Model\ResourceModel\Ebay\Account\CollectionFactory $ebayAccountCollectionFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Ebay\Feedback\CollectionFactory $ebayFeedbackCollectionFactory
     * @param EbayFeedbackTemplateCollectionFactory $ebayFeedbackTemplateCollectionFactory
     * @param \Ess\M2ePro\Model\ResourceModel\Ebay\Account $ebayAccountResource
     * @param \Ess\M2ePro\Model\ResourceModel\Ebay\Feedback $ebayFeedbackResource
     * @param \Ess\M2ePro\Helper\Data $helperData
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Ess\M2ePro\Model\Ebay\Feedback\Manager $ebayFeedbackManager,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Account\CollectionFactory $ebayAccountCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Feedback\CollectionFactory $ebayFeedbackCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Feedback\Template\CollectionFactory $ebayFeedbackTemplateCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Account $ebayAccountResource,
        \Ess\M2ePro\Model\ResourceModel\Ebay\Feedback $ebayFeedbackResource,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct(
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );
        $this->ebayFeedbackManager = $ebayFeedbackManager;
        $this->ebayAccountCollectionFactory = $ebayAccountCollectionFactory;
        $this->ebayFeedbackCollectionFactory = $ebayFeedbackCollectionFactory;
        $this->ebayFeedbackTemplateCollectionFactory = $ebayFeedbackTemplateCollectionFactory;
        $this->ebayAccountResource = $ebayAccountResource;
        $this->ebayFeedbackResource = $ebayFeedbackResource;
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function performActions()
    {
        $feedbacks = $this->getFeedbacksForAnswer(5);
        if (empty($feedbacks)) {
            return;
        }

        foreach ($feedbacks as $feedback) {
            $this->processFeedback($feedback);
        }
    }

    /**
     * @return array
     */
    private function getAccountsIds(): array
    {
        $result = [];

        $ebayAccountCollection = $this->ebayAccountCollectionFactory->create();
        /** @var \Ess\M2ePro\Model\Ebay\Account $ebayAccount */
        foreach ($ebayAccountCollection->getItems() as $ebayAccount) {
            if (!$ebayAccount->isFeedbacksReceive()) {
                continue;
            }

            if ($ebayAccount->isFeedbacksAutoResponseDisabled()) {
                continue;
            }

            if (!$ebayAccount->hasFeedbackTemplate()) {
                continue;
            }

            $result[] = $ebayAccount->getData('account_id');
        }

        return $result;
    }

    /**
     * @param int $daysAgo
     *
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getFeedbacksForAnswer(int $daysAgo): array
    {
        $accountsIds = $this->getAccountsIds();
        if (empty($accountsIds)) {
            return [];
        }

        $accountsIdsTemplate = implode(', ', $accountsIds);
        $feedbackTypePositive = \Ess\M2ePro\Model\Ebay\Feedback::TYPE_POSITIVE;
        $minBuyerFeedbackDate = \Ess\M2ePro\Helper\Date::createCurrentGmt()
                                                       ->modify("-{$daysAgo} days")
                                                       ->format('Y-m-d H:i:s');
        $maxResponseAttemptDate = \Ess\M2ePro\Helper\Date::createCurrentGmt()
                                                         ->modify('-' . self::ATTEMPT_INTERVAL . 'seconds')
                                                         ->format('Y-m-d H:i:s');

        $sqlCondition = <<<SQL
(`main_table`.`seller_feedback_id` = 0)
AND (`main_table`.`is_critical_error_received` = 0)
AND (`main_table`.`buyer_feedback_date` > '{$minBuyerFeedbackDate}')
AND (`last_response_attempt_date` IS NULL OR `last_response_attempt_date` < '{$maxResponseAttemptDate}')
AND (
    `ea`.`feedbacks_auto_response_only_positive` = 0
    OR (`ea`.`feedbacks_auto_response_only_positive` = 1
        AND `main_table`.`buyer_feedback_type` = '{$feedbackTypePositive}'
    )
)
AND `main_table`.`buyer_name` NOT IN (
    SELECT `buyer_name`
    FROM `{$this->ebayFeedbackResource->getMainTable()}`
    WHERE `seller_feedback_id` <> 0
    GROUP BY `buyer_name`
)
SQL;

        $collection = $this->ebayFeedbackCollectionFactory->create();
        $collection->getSelect()
                   ->join(
                       ['ea' => $this->ebayAccountResource->getMainTable()],
                       "`ea`.`account_id` = `main_table`.`account_id` AND `ea`.`account_id` IN ($accountsIdsTemplate)",
                       []
                   )
                   ->where($sqlCondition)
                   ->order(['buyer_feedback_date ASC']);

        return $collection->getItems();
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Feedback $feedback
     *
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Exception
     */
    private function processFeedback(\Ess\M2ePro\Model\Ebay\Feedback $feedback)
    {
        $account = $feedback->getAccount();

        if ($account->getChildObject()->isFeedbacksAutoResponseCycled()) {
            // Load is needed to get correct feedbacks_last_used_id
            $account = $this->parentFactory->getCachedObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'Account',
                $feedback->getData('account_id')
            );
        }

        if (($body = $this->getResponseBody($account)) === '') {
            return;
        }

        $result = $this->ebayFeedbackManager->sendResponse(
            $feedback,
            $body,
            \Ess\M2ePro\Model\Ebay\Feedback::TYPE_POSITIVE
        );

        if ($result) {
            $this->getOperationHistory()->appendText(
                'Send Feedback for "' . $feedback->getData('buyer_name') . '"'
            );
            $this->getOperationHistory()->appendText(
                'His feedback "' . $feedback->getData('buyer_feedback_text')
                . '" (' . $feedback->getData('buyer_feedback_type') . ')'
            );
            $this->getOperationHistory()->appendText('Our Feedback "' . $body . '"');
        } else {
            $this->getOperationHistory()->appendText(
                'Send Feedback for "' . $feedback->getData('buyer_name') . '" was failed'
            );
        }

        $this->getOperationHistory()->saveBufferString();
    }

    private function getResponseBody(\Ess\M2ePro\Model\Account $account)
    {
        if ($account->getChildObject()->isFeedbacksAutoResponseCycled()) {
            $lastUsedId = 0;
            if ($account->getChildObject()->getFeedbacksLastUsedId() != null) {
                $lastUsedId = (int)$account->getChildObject()->getFeedbacksLastUsedId();
            }

            $feedbackTemplatesIds = $this->ebayFeedbackTemplateCollectionFactory->create()
                                                                                ->addFieldToFilter(
                                                                                    'account_id',
                                                                                    $account->getId()
                                                                                )
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
            $feedbackTemplatesIds = $this->ebayFeedbackTemplateCollectionFactory->create()
                                                                                ->addFieldToFilter(
                                                                                    'account_id',
                                                                                    $account->getId()
                                                                                )
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
}
