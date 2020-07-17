<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Synchronization;

/**
 * Class \Ess\M2ePro\Model\Amazon\Template\Synchronization\Diff
 */
class Diff extends \Ess\M2ePro\Model\Template\Synchronization\DiffAbstract
{
    //########################################

    public function isReviseSettingsChanged()
    {
        return $this->isReviseQtyEnabled() ||
               $this->isReviseQtyDisabled() ||
               $this->isReviseQtySettingsChanged() ||
               $this->isRevisePriceEnabled() ||
               $this->isRevisePriceDisabled() ||
               $this->isReviseDetailsDisabled() ||
               $this->isReviseDetailsEnabled() ||
               $this->isReviseImagesDisabled() ||
               $this->isReviseImagesEnabled();
    }

    //########################################

    public function isReviseQtyEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_qty']) && !empty($newSnapshotData['revise_update_qty']);
    }

    public function isReviseQtyDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_qty']) && empty($newSnapshotData['revise_update_qty']);
    }

    // ---------------------------------------

    public function isReviseQtySettingsChanged()
    {
        $keys = [
            'revise_update_qty_max_applied_value_mode',
            'revise_update_qty_max_applied_value',
        ];

        return $this->isSettingsDifferent($keys);
    }

    //########################################

    public function isRevisePriceEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_price']) && !empty($newSnapshotData['revise_update_price']);
    }

    public function isRevisePriceDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_price']) && empty($newSnapshotData['revise_update_price']);
    }

    //########################################

    public function isReviseDetailsEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_details']) && !empty($newSnapshotData['revise_update_details']);
    }

    public function isReviseDetailsDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_details']) && empty($newSnapshotData['revise_update_details']);
    }

    //########################################

    public function isReviseImagesEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_images']) && !empty($newSnapshotData['revise_update_images']);
    }

    public function isReviseImagesDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_images']) && empty($newSnapshotData['revise_update_images']);
    }

    //########################################
}
