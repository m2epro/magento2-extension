<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Orders\MagentoCreationFailed;

use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\Result as TaskResult;
use Ess\M2ePro\Model\Order;

/**
 * Class \Ess\M2ePro\Model\HealthStatus\Task\Orders\MagentoCreationFailed\Ebay
 */
class Ebay extends IssueType
{
    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory */
    private $resultFactory;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    private $ebayFactory;

    private $urlBuilder;
    //########################################

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory $resultFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->resultFactory = $resultFactory;
        $this->ebayFactory   = $ebayFactory;
        $this->urlBuilder    = $urlBuilder;
    }

    //########################################

    public function process()
    {
        $result = $this->resultFactory->create($this);
        $result->setTaskResult(TaskResult::STATE_SUCCESS);

        if ($failedOrders = $this->getCountOfFailedOrders()) {
            $result->setTaskResult(TaskResult::STATE_WARNING);
            $result->setTaskData($failedOrders);
            $result->setTaskMessage($this->getHelper('Module\Translation')->translate([
                <<<HTML
During the last 24 hours, M2E Pro has not created Magento orders for <strong>%failed_orders_count%</strong>
imported Channel orders. See the <a target="_blank" href="%url%">Order Log</a> for more details.
HTML
                ,
                $failedOrders,
                $this->urlBuilder->getUrl('m2epro/ebay_log_order/index', ['magento_order_failed' => true])
            ]));
        }

        return $result;
    }

    //########################################

    private function getCountOfFailedOrders()
    {
        $backToDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $backToDate->modify('- 1 days');

        $collection = $this->ebayFactory->getObject('Order')->getCollection();
        $collection->addFieldToFilter('magento_order_id', ['null' => true]);
        $collection->addFieldToFilter('magento_order_creation_failure', Order::MAGENTO_ORDER_CREATION_FAILED_YES);
        $collection->addFieldToFilter(
            'magento_order_creation_latest_attempt_date',
            ['gt' => $backToDate->format('Y-m-d H:i:s')]
        );

        return $collection->getSize();
    }

    //########################################
}
