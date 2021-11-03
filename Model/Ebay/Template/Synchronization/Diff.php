<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Synchronization;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Synchronization\Diff
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
               $this->isReviseTitleEnabled() ||
               $this->isReviseTitleDisabled() ||
               $this->isReviseSubtitleEnabled() ||
               $this->isReviseSubtitleDisabled() ||
               $this->isReviseDescriptionEnabled() ||
               $this->isReviseDescriptionDisabled() ||
               $this->isReviseImagesEnabled() ||
               $this->isReviseImagesDisabled() ||
               $this->isReviseCategoriesEnabled() ||
               $this->isReviseCategoriesDisabled() ||
               $this->isRevisePartsEnabled() ||
               $this->isRevisePartsDisabled() ||
               $this->isRevisePaymentEnabled() ||
               $this->isRevisePaymentDisabled() ||
               $this->isReviseShippingEnabled() ||
               $this->isReviseShippingDisabled() ||
               $this->isReviseReturnEnabled() ||
               $this->isReviseReturnDisabled() ||
               $this->isReviseOtherEnabled() ||
               $this->isReviseOtherDisabled();
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

    public function isReviseTitleEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_title']) && !empty($newSnapshotData['revise_update_title']);
    }

    public function isReviseTitleDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_title']) && empty($newSnapshotData['revise_update_title']);
    }

    //########################################

    public function isReviseSubtitleEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_sub_title'])
               && !empty($newSnapshotData['revise_update_sub_title']);
    }

    public function isReviseSubtitleDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_sub_title'])
               && empty($newSnapshotData['revise_update_sub_title']);
    }

    //########################################

    public function isReviseDescriptionEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_description'])
               && !empty($newSnapshotData['revise_update_description']);
    }

    public function isReviseDescriptionDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_description'])
               && empty($newSnapshotData['revise_update_description']);
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

    public function isReviseCategoriesEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_categories'])
               && !empty($newSnapshotData['revise_update_categories']);
    }

    public function isReviseCategoriesDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_categories'])
               && empty($newSnapshotData['revise_update_categories']);
    }

    //########################################

    public function isRevisePartsEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_parts'])
            && !empty($newSnapshotData['revise_update_parts']);
    }

    public function isRevisePartsDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_parts'])
            && empty($newSnapshotData['revise_update_parts']);
    }

    //########################################

    public function isRevisePaymentEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_payment']) && !empty($newSnapshotData['revise_update_payment']);
    }

    public function isRevisePaymentDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_payment']) && empty($newSnapshotData['revise_update_payment']);
    }

    //########################################

    public function isReviseShippingEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_shipping']) && !empty($newSnapshotData['revise_update_shipping']);
    }

    public function isReviseShippingDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_shipping']) && empty($newSnapshotData['revise_update_shipping']);
    }

    //########################################

    public function isReviseReturnEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_return']) && !empty($newSnapshotData['revise_update_return']);
    }

    public function isReviseReturnDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_return']) && empty($newSnapshotData['revise_update_return']);
    }

    //########################################

    public function isReviseOtherEnabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return empty($oldSnapshotData['revise_update_other']) && !empty($newSnapshotData['revise_update_other']);
    }

    public function isReviseOtherDisabled()
    {
        $newSnapshotData = $this->newSnapshot;
        $oldSnapshotData = $this->oldSnapshot;

        return !empty($oldSnapshotData['revise_update_other']) && empty($newSnapshotData['revise_update_other']);
    }

    //########################################
}
