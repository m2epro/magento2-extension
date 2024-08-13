<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Promotion;

class TimingManager
{
    private const ATTEMPT_INTERVAL = 86400; // 24 hours

    private \Ess\M2ePro\Model\Registry\Manager $registryManager;

    public function __construct(
        \Ess\M2ePro\Model\Registry\Manager $registryManager
    ) {
        $this->registryManager = $registryManager;
    }

    public function isAttemptIntervalExceeded(string $accountId, string $marketplaceId): bool
    {
        $lastUpdate = $this->getLastProcessed($accountId, $marketplaceId);

        if ($lastUpdate === null) {
            return true;
        }

        $now = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        return $now->getTimestamp() - $lastUpdate->getTimestamp() > self::ATTEMPT_INTERVAL;
    }

    public function setLastProcessed(string $accountId, string $marketplaceId): void
    {
        $this->registryManager->setValue(
            $this->getKey($accountId, $marketplaceId),
            \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s')
        );
    }

    private function getLastProcessed(string $accountId, string $marketplaceId): ?\DateTime
    {
        $lastProcessedToDate = $this->registryManager->getValue($this->getKey($accountId, $marketplaceId));

        if ($lastProcessedToDate !== null) {
            $lastProcessedToDate = \Ess\M2ePro\Helper\Date::createDateGmt($lastProcessedToDate);
        }

        return $lastProcessedToDate;
    }

    private function getKey(string $accountId, string $marketplaceId): string
    {
        return "/ebay/promotions/synchronize/{$accountId}/{$marketplaceId}/";
    }
}
