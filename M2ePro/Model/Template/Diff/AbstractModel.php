<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Template\Diff;

/**
 * Class \Ess\M2ePro\Model\Template\Diff\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\AbstractModel
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

    public function setOldSnapshot(array $snapshot)
    {
        $this->oldSnapshot = $snapshot;
        return $this;
    }

    //########################################

    abstract public function isDifferent();

    //########################################

    protected function isSettingsDifferent($keys, $groupKey = null)
    {
        $newSnapshotData = $this->newSnapshot;
        if ($groupKey !== null && isset($newSnapshotData[$groupKey])) {
            $newSnapshotData = $newSnapshotData[$groupKey];
        }

        $oldSnapshotData = $this->oldSnapshot;
        if ($groupKey !== null && isset($oldSnapshotData[$groupKey])) {
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
