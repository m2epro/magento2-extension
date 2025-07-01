<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\AfnQty;

use Ess\M2ePro\Helper\Date as DateHelper;

class AccountUpdateIntervalManager
{
    private const REGISTRY_PREFIX = '/amazon/inventory/afn_qty/by_account/';
    private const REGISTRY_SUFFIX = '/last_update/';

    private \Ess\M2ePro\Model\Registry\Manager $registryManager;

    public function __construct(
        \Ess\M2ePro\Model\Registry\Manager $registryManager
    ) {
        $this->registryManager = $registryManager;
    }

    public function isIntervalExceeded(int $accountId, int $interval): bool
    {
        $lastUpdate = $this->registryManager->getValue($this->getRegistryKey($accountId));
        if (!$lastUpdate) {
            return true;
        }

        $now = DateHelper::createCurrentGmt();
        $lastUpdateDate = DateHelper::createDateGmt($lastUpdate);

        return $now->getTimestamp() - $lastUpdateDate->getTimestamp() > $interval;
    }

    public function setAccountLastUpdateNow(int $accountId): void
    {
        $now = DateHelper::createCurrentGmt();
        $this->setAccountLastUpdate(
            $accountId,
            $now->format('Y-m-d H:i:s')
        );
    }

    public function resetAccountLastUpdate(int $accountId): void
    {
        $this->setAccountLastUpdate($accountId, null);
    }

    private function setAccountLastUpdate(int $accountId, ?string $value): void
    {
        $this->registryManager->setValue(
            $this->getRegistryKey($accountId),
            $value
        );
    }

    private function getRegistryKey(int $accountId): string
    {
        return self::REGISTRY_PREFIX . $accountId . self::REGISTRY_SUFFIX;
    }
}
