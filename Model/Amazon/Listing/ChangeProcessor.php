<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\ChangeProcessor
 */
class ChangeProcessor extends \Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\ChangeProcessorAbstract
{
    const INSTRUCTION_TYPE_CONDITION_DATA_CHANGED = 'listing_condition_data_changed';
    const INSTRUCTION_TYPE_SKU_SETTINGS_CHANGED   = 'listing_sku_settings_changed';

    const INSTRUCTION_INITIATOR = 'listing_change_processor';

    //########################################

    protected function getInstructionInitiator()
    {
        return self::INSTRUCTION_INITIATOR;
    }

    // ---------------------------------------

    protected function getInstructionsData(\Ess\M2ePro\Model\ActiveRecord\Diff $diff, $status)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Diff $diff */

        $data = [];

        if ($diff->isQtyDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 40;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_QTY_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        if ($diff->isConditionDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_CONDITION_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        if ($diff->isDetailsDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        if ($diff->isImagesDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_IMAGES_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        if ($diff->isSkuSettingsDifferent()) {
            $priority = 0;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_SKU_SETTINGS_CHANGED,
                'priority' => $priority,
            ];
        }

        return $data;
    }

    //########################################
}
