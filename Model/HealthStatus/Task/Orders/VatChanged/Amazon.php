<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Task\Orders\VatChanged;

use DateTime;
use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\Result as TaskResult;

class Amazon extends IssueType
{
    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory */
    private $resultFactory;
    /** @var \Magento\Framework\UrlInterface */
    private $urlBuilder;
    /** @var \Ess\M2ePro\Model\ResourceModel\Order\Log\CollectionFactory */
    private $orderLogCollectionFactory;

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory $resultFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Ess\M2ePro\Model\ResourceModel\Order\Log\CollectionFactory $orderLogCollectionFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->resultFactory = $resultFactory;
        $this->urlBuilder = $urlBuilder;
        $this->orderLogCollectionFactory = $orderLogCollectionFactory;
    }

    public function process(): TaskResult
    {
        $result = $this->resultFactory->create($this);
        $result->setTaskResult(TaskResult::STATE_SUCCESS);

        $fromDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $fromDate->modify('-1 day');

        if (($failedOrders = $this->getCountOfFailedOrders($fromDate)) === 0) {
            return $result;
        }

        $result->setTaskResult(TaskResult::STATE_WARNING);
        $result->setTaskData($failedOrders);

        $taskMessage = __('
            During the last 24 hours, Amazon applied reverse charge (0% VAT) to <strong>%failed_orders_count</strong>
            orders. See the <a target="_blank" href="%url">Order Log</a> for more details.
        ', ['failed_orders_count' => $failedOrders, 'url' => $this->urlToLogPage($fromDate)]);

        $result->setTaskMessage($taskMessage);

        return $result;
    }

    private function getCountOfFailedOrders(DateTime $fromDate): int
    {
        $logCollection = $this->orderLogCollectionFactory->create();
        $logCollection->onlyVatChanged();
        $logCollection->createdDateGreaterThenOrEqual($fromDate);
        $logCollection->getSelect()->group('main_table.order_id');

        return $logCollection->getSize();
    }

    private function urlToLogPage(DateTime $fromDate): string
    {
        $filter = http_build_query([
            'create_date' => [
                'from' => \Ess\M2ePro\Helper\Date::convertToLocalFormat($fromDate),
                'locale' => \Ess\M2ePro\Helper\Date::getLocaleResolver()->getLocale(),
            ],
        ]);

        return $this->urlBuilder->getUrl('m2epro/amazon_log_order/index', [
            'orders_with_modified_vat' => true,
            'filter' => base64_encode($filter),
        ]);
    }
}
