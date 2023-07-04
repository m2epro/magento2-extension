<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics;

class Registry
{
    public const STORAGE_KEY = 'servicing/analytics';
    /** @var array $storageData */
    private $storageData = [];
    /** @var bool  */
    private $isUpdateStorageData = false;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registry;

    public function __construct(
        \Ess\M2ePro\Model\Registry\Manager $registry
    ) {
        $this->registry = $registry;
    }

    // ----------------------------------------

    public function isPlannedNow(): bool
    {
        $plannedAt = $this->getPlannedAt();
        $startedAt = $this->getStartedAt();
        $finishedAt = $this->getFinishedAt();

        $currentTimestamp = \Ess\M2ePro\Helper\Date::createCurrentGmt()->getTimestamp();
        if (
            empty($plannedAt) ||
            (int)\Ess\M2ePro\Helper\Date::createDateGmt($plannedAt)->format('U') > $currentTimestamp
        ) {
            return false;
        }

        if (!empty($startedAt) && !empty($finishedAt)) {
            return false;
        }

        return true;
    }

    public function getPlannedAt()
    {
        $regData = $this->getStoredData();

        return $regData['planned_at'] ?? null;
    }

    public function getStartedAt()
    {
        $regData = $this->getStoredData();

        return $regData['started_at'] ?? null;
    }

    public function getFinishedAt()
    {
        $regData = $this->getStoredData();

        return $regData['finished_at'] ?? null;
    }

    // ---------------------------------------

    public function markPlannedAt($date): void
    {
        $regData = $this->getStoredData();

        $regData['planned_at'] = $date;
        unset($regData['started_at'], $regData['finished_at'], $regData['progress']);

        $this->setStoredData($regData);
    }

    public function markStarted(): void
    {
        $regData = $this->getStoredData();
        $regData['started_at'] = \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');
        $regData['progress'] = [];

        $this->setStoredData($regData);
    }

    public function markFinished(): void
    {
        $regData = $this->getStoredData();
        $regData['finished_at'] = \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s');

        $this->setStoredData($regData);
    }

    // ----------------------------------------

    public function getProgressData($nick, $progressDataKey)
    {
        $regData = $this->getStoredData();

        return $regData['progress'][$nick][$progressDataKey] ?? null;
    }

    public function setProgressData($nick, $progressDataKey, $progressDataValue): void
    {
        $regData = $this->getStoredData();
        $regData['progress'][$nick][$progressDataKey] = $progressDataValue;

        $this->setStoredData($regData);
    }

    // ----------------------------------------

    protected function setStoredData($data): void
    {
        $this->registry->setValue(self::STORAGE_KEY, $data);
        $this->isUpdateStorageData = false;
    }

    /**
     * @return array|bool|null
     */
    protected function getStoredData()
    {
        if (!$this->isUpdateStorageData) {
            $this->storageData = $this->registry->getValueFromJson(self::STORAGE_KEY);
            $this->isUpdateStorageData = true;
        }

        return $this->storageData;
    }
}
