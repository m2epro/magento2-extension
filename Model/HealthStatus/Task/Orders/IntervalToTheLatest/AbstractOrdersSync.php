<?php

namespace Ess\M2ePro\Model\HealthStatus\Task\Orders\IntervalToTheLatest;

use Ess\M2ePro\Model\HealthStatus\Task\IssueType;
use Ess\M2ePro\Model\HealthStatus\Task\Result as TaskResult;

abstract class AbstractOrdersSync extends IssueType
{
    private const MAX_VALUE_OF_INTERVAL = 3600 * 4;

    /** @var \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory */
    private $resultFactory;
    /** @var \Ess\M2ePro\Model\Order\SyncStatusManager */
    private $syncStatusManager;
    /** @var \Magento\Backend\Model\UrlInterface */
    private $urlBuilder;

    public function __construct(
        \Ess\M2ePro\Model\HealthStatus\Task\Result\Factory $resultFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Model\Order\SyncStatusManager $syncStatusManager
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->resultFactory = $resultFactory;
        $this->urlBuilder = $urlBuilder;
        $this->syncStatusManager = $syncStatusManager;
    }

    abstract protected function getComponent(): string;
    abstract protected function getErrorText(): string;

    public function process(): \Ess\M2ePro\Model\HealthStatus\Task\Result
    {
        $result = $this->resultFactory->create($this);
        $result->setTaskResult(TaskResult::STATE_SUCCESS);

        $syncStatus = $this->syncStatusManager->getSyncStatus($this->getComponent());

        if ($syncStatus === null || $syncStatus->isSuccess()) {
            return $result;
        }

        $currentDateTime = \Ess\M2ePro\Helper\Date::createCurrentGmt();
        $firstErrorTime = $syncStatus->getFirstErrorDate();

        if ($firstErrorTime === null) {
            return $result;
        }

        $diffInSeconds = $currentDateTime->getTimestamp() - $firstErrorTime->getTimestamp();
        if ($diffInSeconds <= self::MAX_VALUE_OF_INTERVAL) {
            return $result;
        }

        $result->setTaskResult(TaskResult::STATE_CRITICAL);
        $result->setTaskMessage($this->getErrorText());

        return $result;
    }

    protected function getUrlBuilder(): \Magento\Backend\Model\UrlInterface
    {
        return $this->urlBuilder;
    }
}
