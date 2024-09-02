<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Action;

class TimeManager
{
    private const INTERVAL_ACTION_UPDATE = 3600;
    private const INTERVAL_ACTION_CANCEL = 7200;
    private const INTERVAL_ACTION_REFUND = 7200;

    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registryManager;

    public function __construct(
        \Ess\M2ePro\Model\Registry\Manager $registryManager
    ) {
        $this->registryManager = $registryManager;
    }

    public function isTimeToProcessUpdate(string $merchantId): bool
    {
        $lastProcessDate = $this->registryManager->getValue($this->getUpdateKey($merchantId));
        if (empty($lastProcessDate)) {
            return true;
        }

        return $this->isTime($lastProcessDate, self::INTERVAL_ACTION_UPDATE);
    }

    public function isTimeToProcessCancel(string $merchantId): bool
    {
        $lastProcessDate = $this->registryManager->getValue($this->getCancelKey($merchantId));
        if (empty($lastProcessDate)) {
            return true;
        }

        return $this->isTime($lastProcessDate, self::INTERVAL_ACTION_CANCEL);
    }

    public function isTimeToProcessRefund(string $merchantId): bool
    {
        $lastProcessDate = $this->registryManager->getValue($this->getRefundKey($merchantId));
        if (empty($lastProcessDate)) {
            return true;
        }

        return $this->isTime($lastProcessDate, self::INTERVAL_ACTION_REFUND);
    }

    public function setLastUpdate(string $merchantId, \DateTime $date): void
    {
        $this->registryManager->setValue($this->getUpdateKey($merchantId), $date->format('Y-m-d H:i:s'));
    }

    public function setLastCancel(string $merchantId, \DateTime $date): void
    {
        $this->registryManager->setValue($this->getCancelKey($merchantId), $date->format('Y-m-d H:i:s'));
    }

    public function setLastRefund(string $merchantId, \DateTime $date): void
    {
        $this->registryManager->setValue($this->getRefundKey($merchantId), $date->format('Y-m-d H:i:s'));
    }

    // ----------------------------------------

    private function isTime(string $lastRun, int $interval): bool
    {
        $lastProcessDate = \Ess\M2ePro\Helper\Date::createDateGmt($lastRun);

        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        return $lastProcessDate->getTimestamp() < ($currentDate->getTimestamp() - $interval);
    }

    private function getUpdateKey(string $merchantId): string
    {
        return "/amazon/orders/update/{$merchantId}/process_date/";
    }

    private function getCancelKey(string $merchantId): string
    {
        return "/amazon/orders/cancel/{$merchantId}/process_date/";
    }

    private function getRefundKey(string $merchantId): string
    {
        return "/amazon/orders/refund/{$merchantId}/process_date/";
    }
}
