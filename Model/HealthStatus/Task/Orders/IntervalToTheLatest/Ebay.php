<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Orders\IntervalToTheLatest;

use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\Result as TaskResult;

class Ebay extends IssueType
{
    const DIFF_CRITICAL_FACTOR = 1.50;
    const DIFF_WARNING_FACTOR  = 1.00;

    const MINIMUM_VALUE_OF_INTERVAL = 3600 * 12;
    const MINIMUM_COUNT_OF_ORDERS   = 7;

    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory */
    private $resultFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    private $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory $resultFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ){
        parent::__construct($helperFactory, $modelFactory);
        $this->resultFactory = $resultFactory;
        $this->ebayFactory = $ebayFactory;
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
It was detected that there were no orders imported into your M2E Pro during the latest <b>%interval%</b> hours.
Such case might be a sequence of some issue with your Module running (e.g. failed Install/Upgrade processes,
incorrect settings for Magento Cron and inability to use M2E Pro Cron Service, lost connection to M2E Pro Servers,
error returned from Channel for Account access, etc.).<br>
Thus, please, verify all the aspects related to the Installation/Upgrade and configuration of your M2E Pro Module.
It might be helpful to involve your Developer/Administrator into this process along with surfing the
<a href="%documentation%" target="_blank">Documentation</a> and
<a href="%knowledgebase%" target="_blank">Knowledge Base</a>.<br>
In case, any assistance is needed, you can consult with our Support Team via email
<a href="mailto:support@m2epro.com">support@m2epro.com</a>.
HTML
                ,
                ceil($currentInterval / 3600),
                'http://docs.m2epro.com/x/u4AVAQ',
                'https://support.m2epro.com/knowledgebase'
            ]));
        }

        if ($currentInterval >= $maxInterval * self::DIFF_CRITICAL_FACTOR) {

            $result->setTaskResult(TaskResult::STATE_CRITICAL);
            $result->setTaskMessage($this->getHelper('Module\Translation')->translate([
                <<<HTML
It seems that there were no orders imported into your M2E Pro during the latest <b>%interval%</b> hours
which is rather unusual. We strongly recommend you to ensure that there were purchases made on Channel.<br>
If the purchases are available on Channel, there might be some issue with your Module running
(e.g. failed Install/Upgrade processes, incorrect settings for Magento Cron and inability to use M2E Pro Cron Service,
lost connection to M2E Pro Servers, error returned from Channel for Account access, etc.).<br>
Thus, please, verify all the aspects related to the Installation/Upgrade and configuration of your M2E Pro Module.
It might be helpful to involve your Developer/Administrator into this process along with surfing the
<a href="%documentation%" target="_blank">Documentation</a> and
<a href="%knowledgebase%" target="_blank">Knowledge Base</a>.<br>
In case, any assistance is needed, you can consult with our Support Team via email
<a href="mailto:support@m2epro.com">support@m2epro.com</a>.
HTML
                ,
                ceil($currentInterval / 3600),
                'http://docs.m2epro.com/x/u4AVAQ',
                'https://support.m2epro.com/knowledgebase'
            ]));
        }

        return $result;
    }

    //########################################

    private function isNeedToCalculate()
    {
        $backToDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $backToDate->modify('- 7 days');

        $collection = $this->ebayFactory->getObject('Order')->getCollection();
        $collection->addFieldToFilter('purchase_create_date', ['gt' => $backToDate->format('Y-m-d H:i:s')]);

        return $collection->getSize() >= self::MINIMUM_COUNT_OF_ORDERS;
    }

    private function calculateMaxIntervalBetweenOrders()
    {
        $backToDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $backToDate->modify('- 1 month');

        $collection = $this->ebayFactory->getObject('Order')->getCollection();
        $collection->addFieldToFilter('purchase_create_date', ['gt' => $backToDate->format('Y-m-d H:i:s')]);
        $collection->setOrder('purchase_create_date', $collection::SORT_ORDER_DESC);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(['second_table.purchase_create_date']);
        $collection->getSelect()->limit(20000);

        $intervals = [];
        $previousItemDate = null;

        foreach ($collection->getItems() as $item){

            $currentItemDate = new \DateTime($item->getData('purchase_create_date'), new \DateTimeZone('UTC'));

            if (is_null($previousItemDate)) {

                $previousItemDate = $currentItemDate;
                continue;
            }

            $diff = $previousItemDate->getTimestamp() - $currentItemDate->getTimestamp();
            $intervals[] = $diff;

            $previousItemDate = $currentItemDate;
        }

        $maxInterval = max($intervals);
        $maxInterval < self::MINIMUM_VALUE_OF_INTERVAL && $maxInterval = self::MINIMUM_VALUE_OF_INTERVAL;

        return $maxInterval;
    }

    private function getCurrentIntervalToLatestOrder()
    {
        $collection = $this->ebayFactory->getObject('Order')->getCollection();
        $collection->setOrder('purchase_create_date', $collection::SORT_ORDER_DESC);

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns(['second_table.purchase_create_date']);
        $collection->getSelect()->limit(1);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $latestOrderDate = $collection->getFirstItem()->getData('purchase_create_date');
        $latestOrderDate = new \DateTime($latestOrderDate, new \DateTimeZone('UTC'));

        return $now->getTimestamp() - $latestOrderDate->getTimestamp();
    }

    //########################################
}