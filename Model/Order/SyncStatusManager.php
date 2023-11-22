<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Order;

class SyncStatusManager
{
    private const STATUS_FAIL = 'fail';
    private const STATUS_SUCCESS = 'success';

    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registryManager;

    public function __construct(
        \Ess\M2ePro\Model\Registry\Manager $registryManager
    ) {
        $this->registryManager = $registryManager;
    }

    public function setLastRunAsSuccess(string $componentNick): void
    {
        $this->setStatus($componentNick, self::STATUS_SUCCESS);
        $this->setLastRun($componentNick);
        $this->clearFailData($componentNick);
    }

    public function setLastRunAsFail(string $componentNick): void
    {
        $this->setStatus($componentNick, self::STATUS_FAIL);
        $this->setLastRun($componentNick);
        $this->setFirstErrorDate($componentNick);
    }

    public function getSyncStatus(string $componentNick): ?SyncStatus
    {
        $lastRun = $this->getLastRun($componentNick);
        if ($lastRun === null) {
            return null;
        }

        return new SyncStatus(
            $this->getStatus($componentNick) === self::STATUS_SUCCESS,
            $lastRun,
            $this->getFirstErrorDate($componentNick)
        );
    }

    // ----------------------------------------

    private function setStatus(string $componentNick, string $status): void
    {
        $this->registryManager->setValue($this->getStatusKey($componentNick), $status);
    }

    private function getStatus(string $componentNick): ?string
    {
        return $this->registryManager->getValue($this->getStatusKey($componentNick));
    }

    private function setLastRun(string $componentNick): void
    {
        $this->registryManager->setValue(
            $this->getLastRunKey($componentNick),
            \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s')
        );
    }

    private function getLastRun(string $componentNick): ?\DateTime
    {
        $lastRun = $this->registryManager->getValue($this->getLastRunKey($componentNick));
        if ($lastRun === null) {
            return null;
        }

        return \Ess\M2ePro\Helper\Date::createDateGmt($lastRun);
    }

    private function clearFailData(string $componentNick): void
    {
        $this->registryManager->deleteValue($this->getFirstErrorKey($componentNick));
    }

    private function setFirstErrorDate(string $componentNick): void
    {
        $firstDate = $this->registryManager->getValue($this->getFirstErrorKey($componentNick));
        if ($firstDate !== null) {
            return;
        }

        $this->registryManager->setValue(
            $this->getFirstErrorKey($componentNick),
            \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s')
        );
    }

    private function getFirstErrorDate(string $componentNick): ?\DateTime
    {
        $firstDate = $this->registryManager->getValue($this->getFirstErrorKey($componentNick));
        if ($firstDate === null) {
            return null;
        }

        return \Ess\M2ePro\Helper\Date::createDateGmt($firstDate);
    }

    private function getStatusKey(string $componentNick): string
    {
        return "/order/sync_status/{$componentNick}/status/";
    }

    private function getLastRunKey(string $componentNick): string
    {
        return "/order/sync_status/{$componentNick}/last_run/";
    }

    private function getFirstErrorKey(string $componentNick): string
    {
        return "/order/sync_status/{$componentNick}/first_error_date/";
    }
}
