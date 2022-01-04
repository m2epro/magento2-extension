<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Orders\IntervalToTheLatest;

use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\Result as TaskResult;

/**
 * Class \Ess\M2ePro\Model\HealthStatus\Task\Orders\IntervalToTheLatest\Amazon
 */
class Amazon extends IssueType
{
    const DIFF_CRITICAL_FACTOR = 1.50;
    const DIFF_WARNING_FACTOR  = 1.00;

    const MINIMUM_VALUE_OF_INTERVAL = 3600 * 12;
    const MINIMUM_COUNT_OF_ORDERS   = 7;

    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory */
    private $resultFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    private $amazonFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory $resultFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->resultFactory = $resultFactory;
        $this->amazonFactory = $amazonFactory;
    }

    //########################################

    public function process()
    {
        $result = $this->resultFactory->create($this);
        $result->setTaskResult(TaskResult::STATE_SUCCESS);

        if (!$this->isNeedToCalculate()) {
            return $result;
        }

        $maxInterval     = $this->calculateMaxIntervalBetweenOrders();
        $currentInterval = $this->getCurrentIntervalToLatestOrder();

        $result->setTaskData($currentInterval);

        if ($currentInterval >= $maxInterval * self::DIFF_WARNING_FACTOR) {
            $result->setTaskResult(TaskResult::STATE_WARNING);
            $result->setTaskMessage($this->getHelper('Module\Translation')->translate([
            <<<HTML
Channel orders have not been imported into M2E Pro for the last %interval% hours. 
Please make sure that the Cron Service and Server connection are properly configured, 
and the last M2E Pro installation/upgrade went well.
If you need assistance, contact Support at <a href="support@m2epro.com">support@m2epro.com</a>.
HTML
                ,
                ceil($currentInterval / 3600),
                $this->getHelper('Module_Support')->getKnowledgebaseArticleUrl('692955'),
                $this->getHelper('Module_Support')->getKnowledgebaseArticleUrl('664870')
            ]));
        }

        if ($currentInterval >= $maxInterval * self::DIFF_CRITICAL_FACTOR) {
            $result->setTaskResult(TaskResult::STATE_CRITICAL);
            $result->setTaskMessage($this->getHelper('Module\Translation')->translate([
                <<<HTML
Channel orders have not been imported into M2E Pro for the last %interval% hours. 
Please make sure that the Cron Service and Server connection are properly configured, 
and the last M2E Pro installation/upgrade went well.
If you need assistance, contact Support at <a href="support@m2epro.com">support@m2epro.com</a>.
HTML
                ,
                ceil($currentInterval / 3600),
                $this->getHelper('Module_Support')->getKnowledgebaseArticleUrl('692955'),
                $this->getHelper('Module_Support')->getKnowledgebaseArticleUrl('664870')
            ]));
        }

        return $result;
    }

    //########################################

    private function isNeedToCalculate()
    {
        $backToDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $backToDate->modify('- 7 days');

        $collection = $this->amazonFactory->getObject('Order')->getCollection();
        $collection->addFieldToFilter('purchase_create_date', ['gt' => $backToDate->format('Y-m-d H:i:s')]);

        return $collection->getSize() >= self::MINIMUM_COUNT_OF_ORDERS;
    }

    private function calculateMaxIntervalBetweenOrders()
    {
        $backToDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $backToDate->modify('- 1 month');

        $collection = $this->amazonFactory->getObject('Order')->getCollection();
        $collection->addFieldToFilter('purchase_create_date', ['gt' => $backToDate->format('Y-m-d H:i:s')]);
        $collection->setOrder('purchase_create_date', $collection::SORT_ORDER_DESC);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns(['second_table.purchase_create_date']);
        $collection->getSelect()->limit(20000);

        $intervals = [];
        $maxInterval = 0;
        $previousItemDate = null;

        foreach ($collection->getItems() as $item) {
            $currentItemDate = new \DateTime($item->getData('purchase_create_date'), new \DateTimeZone('UTC'));

            if ($previousItemDate === null) {
                $previousItemDate = $currentItemDate;
                continue;
            }

            $diff = $previousItemDate->getTimestamp() - $currentItemDate->getTimestamp();
            $intervals[] = $diff;

            $previousItemDate = $currentItemDate;
        }

        !empty($intervals) && $maxInterval = max($intervals);
        $maxInterval < self::MINIMUM_VALUE_OF_INTERVAL && $maxInterval = self::MINIMUM_VALUE_OF_INTERVAL;

        return $maxInterval;
    }

    private function getCurrentIntervalToLatestOrder()
    {
        $collection = $this->amazonFactory->getObject('Order')->getCollection();
        $collection->setOrder('purchase_create_date', $collection::SORT_ORDER_DESC);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns(['second_table.purchase_create_date']);
        $collection->getSelect()->limit(1);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $latestOrderDate = $collection->getFirstItem()->getData('purchase_create_date');
        $latestOrderDate = new \DateTime($latestOrderDate, new \DateTimeZone('UTC'));

        return $now->getTimestamp() - $latestOrderDate->getTimestamp();
    }

    //########################################
}
