<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Receive\InvoiceData;

class MerchantIntervalManager
{
    private \Ess\M2ePro\Model\Registry\Manager $registryManager;

    public function __construct(\Ess\M2ePro\Model\Registry\Manager $registryManager)
    {
        $this->registryManager = $registryManager;
    }

    public function isLastRunLessThenHours(string $merchantId, int $hours): bool
    {
        $lastRunDate = $this->getLastSendDate($merchantId);
        if (empty($lastRunDate)) {
            return true;
        }

        $fourHourAgo = \Ess\M2ePro\Helper\Date::createCurrentGmt()->modify("- $hours hour");

        return $fourHourAgo->getTimestamp() >= $lastRunDate->getTimestamp();
    }

    public function updateLastSendDate(string $merchantId): void
    {
        $nowDate = \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');
        $this->registryManager->setValue($this->makeRegistryKey($merchantId), $nowDate);
    }

    private function getLastSendDate(string $merchantId): ?\DateTime
    {
        $value = $this->registryManager->getValue($this->makeRegistryKey($merchantId));
        if (empty($value)) {
            return null;
        }

        return \Ess\M2ePro\Helper\Date::createDateGmt($value);
    }

    private function makeRegistryKey(string $merchantId): string
    {
        return "/cron/task/amazon/order/receive/invoice_data/$merchantId/last_run/";
    }
}
