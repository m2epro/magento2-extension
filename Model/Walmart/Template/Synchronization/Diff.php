<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\Synchronization;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\Synchronization\Diff
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
               $this->isRevisePromotionsEnabled() ||
               $this->isRevisePromotionsDisabled() ||
               $this->isReviseDetailsEnabled() ||
               $this->isReviseDetailsDisabled();
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

    public function isRevisePromotionsEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_promotions']) &&
              !empty($newSnapshotData['revise_update_promotions']);
    }

    public function isRevisePromotionsDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_promotions']) &&
                empty($newSnapshotData['revise_update_promotions']);
    }

    //########################################

    public function isReviseDetailsEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_details']) &&
               !empty($newSnapshotData['revise_update_details']);
    }

    public function isReviseDetailsDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_details']) &&
               empty($newSnapshotData['revise_update_details']);
    }

    //########################################
}
