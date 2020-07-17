<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\Synchronization;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\Synchronization\ChangeProcessor
 */
class ChangeProcessor extends \Ess\M2ePro\Model\Template\Synchronization\ChangeProcessorAbstract
{
    const INSTRUCTION_TYPE_REVISE_QTY_ENABLED            = 'template_synchronization_revise_qty_enabled';
    const INSTRUCTION_TYPE_REVISE_QTY_DISABLED           = 'template_synchronization_revise_qty_disabled';
    const INSTRUCTION_TYPE_REVISE_QTY_SETTINGS_CHANGED   = 'template_synchronization_revise_qty_settings_changed';

    const INSTRUCTION_TYPE_REVISE_PRICE_ENABLED          = 'template_synchronization_revise_price_enabled';
    const INSTRUCTION_TYPE_REVISE_PRICE_DISABLED         = 'template_synchronization_revise_price_disabled';

    const INSTRUCTION_TYPE_REVISE_PROMOTIONS_ENABLED     = 'template_synchronization_revise_promotions_enabled';
    const INSTRUCTION_TYPE_REVISE_PROMOTIONS_DISABLED    = 'template_synchronization_revise_promotions_disabled';

    const INSTRUCTION_TYPE_REVISE_DETAILS_ENABLED     = 'template_synchronization_revise_details_enabled';
    const INSTRUCTION_TYPE_REVISE_DETAILS_DISABLED    = 'template_synchronization_revise_details_disabled';

    //########################################

    protected function getInstructionsData(\Ess\M2ePro\Model\ActiveRecord\Diff $diff, $status)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Template\Synchronization\Diff $diff */

        $data = parent::getInstructionsData($diff, $status);

        if ($diff->isReviseQtyEnabled()) {
            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_REVISE_QTY_ENABLED,
                'priority'  => $status === \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED ? 80: 5,
            ];
        } elseif ($diff->isReviseQtyDisabled()) {
            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_REVISE_QTY_DISABLED,
                'priority'  => 5,
            ];
        } elseif ($diff->isReviseQtySettingsChanged()) {
            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_REVISE_QTY_SETTINGS_CHANGED,
                'priority'  => $status === \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED ? 80: 5,
            ];
        }

        //----------------------------------------

        if ($diff->isRevisePriceEnabled()) {
            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_REVISE_PRICE_ENABLED,
                'priority'  => $status === \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED ? 60: 5,
            ];
        } elseif ($diff->isRevisePriceDisabled()) {
            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_REVISE_PRICE_DISABLED,
                'priority'  => 5,
            ];
        }

        //----------------------------------------

        if ($diff->isRevisePromotionsEnabled()) {
            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_REVISE_PROMOTIONS_ENABLED,
                'priority'  => $status === \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED ? 30: 5,
            ];
        } elseif ($diff->isRevisePromotionsDisabled()) {
            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_REVISE_PROMOTIONS_DISABLED,
                'priority'  => 5,
            ];
        }

        //----------------------------------------

        if ($diff->isReviseDetailsEnabled()) {
            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_REVISE_DETAILS_ENABLED,
                'priority'  => $status === \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED ? 30: 5,
            ];
        } elseif ($diff->isReviseDetailsDisabled()) {
            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_REVISE_DETAILS_DISABLED,
                'priority'  => 5,
            ];
        }

        return $data;
    }

    //########################################
}
