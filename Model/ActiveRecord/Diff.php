<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord;

/**
 * Class \Ess\M2ePro\Model\ActiveRecord\Diff
 */
class Diff extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var array */
    protected $newSnapshot = [];

    /** @var array */
    protected $oldSnapshot = [];

    //########################################

    public function setNewSnapshot(array $snapshot)
    {
        $this->newSnapshot = $snapshot;
        return $this;
    }

    public function getNewSnapShot()
    {
        return $this->newSnapshot;
    }

    public function setOldSnapshot(array $snapshot)
    {
        $this->oldSnapshot = $snapshot;
        return $this;
    }

    public function getOldSnapShot()
    {
        return $this->oldSnapshot;
    }

    //########################################

    public function isDifferent()
    {
        return $this->newSnapshot !== $this->oldSnapshot;
    }

    //########################################

    protected function isSettingsDifferent($keys, $groupKey = NULL)
    {
        $newSnapshotData = $this->newSnapshot;
        if (null !== $groupKey && isset($newSnapshotData[$groupKey])) {
            $newSnapshotData = $newSnapshotData[$groupKey];
        }

        $oldSnapshotData = $this->oldSnapshot;
        if (null !== $groupKey && isset($oldSnapshotData[$groupKey])) {
            $oldSnapshotData = $oldSnapshotData[$groupKey];
        }

        foreach ($keys as $key) {
            if (empty($newSnapshotData[$key]) && empty($oldSnapshotData[$key])) {
                continue;
            }

            if (empty($newSnapshotData[$key]) || empty($oldSnapshotData[$key])) {
                return true;
            }

            if ($newSnapshotData[$key] != $oldSnapshotData[$key]) {
                return true;
            }
        }

        return false;
    }

    //########################################
}
