<?php

namespace Ess\M2ePro\Model\Amazon\Template\Synchronization;

class Diff extends \Ess\M2ePro\Model\Template\Synchronization\DiffAbstract
{
    public function isReviseSettingsChanged(): bool
    {
        return $this->isReviseQtyEnabled() ||
            $this->isReviseQtyDisabled() ||
            $this->isReviseQtySettingsChanged() ||
            $this->isRevisePriceEnabled() ||
            $this->isRevisePriceDisabled() ||
            $this->isReviseDetailsDisabled() ||
            $this->isReviseDetailsEnabled();
    }

    //########################################

    public function isReviseQtyEnabled(): bool
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_qty']) && !empty($newSnapshotData['revise_update_qty']);
    }

    public function isReviseQtyDisabled(): bool
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_qty']) && empty($newSnapshotData['revise_update_qty']);
    }

    // ---------------------------------------

    public function isReviseQtySettingsChanged(): bool
    {
        $keys = [
            'revise_update_qty_max_applied_value_mode',
            'revise_update_qty_max_applied_value',
        ];

        return $this->isSettingsDifferent($keys);
    }

    // ---------------------------------------

    public function isRevisePriceEnabled(): bool
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_price']) && !empty($newSnapshotData['revise_update_price']);
    }

    public function isRevisePriceDisabled(): bool
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_price']) && empty($newSnapshotData['revise_update_price']);
    }

    // ---------------------------------------

    public function isReviseDetailsEnabled(): bool
    {
        return $this->isSettingEnabled('revise_update_details')
            || $this->isSettingEnabled('revise_update_main_details')
            || $this->isSettingEnabled('revise_update_images');
    }

    public function isReviseDetailsDisabled(): bool
    {
        return $this->isSettingDisabled('revise_update_details')
            || $this->isSettingDisabled('revise_update_main_details')
            || $this->isSettingDisabled('revise_update_images');
    }

    private function isSettingEnabled(string $key): bool
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData[$key]) && !empty($newSnapshotData[$key]);
    }

    private function isSettingDisabled(string $key): bool
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData[$key]) && empty($newSnapshotData[$key]);
    }
}
