<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Template\Synchronization;

/**
 * Class \Ess\M2ePro\Model\Template\Synchronization\ChangeProcessorAbstract
 */
abstract class ChangeProcessorAbstract extends \Ess\M2ePro\Model\Template\ChangeProcessorAbstract
{
    const INSTRUCTION_INITIATOR = 'template_synchronization_change_processor';

    const INSTRUCTION_TYPE_LIST_MODE_ENABLED       = 'template_synchronization_list_mode_enabled';
    const INSTRUCTION_TYPE_LIST_MODE_DISABLED      = 'template_synchronization_list_mode_disabled';
    const INSTRUCTION_TYPE_LIST_SETTINGS_CHANGED   = 'template_synchronization_list_settings_changed';

    const INSTRUCTION_TYPE_RELIST_MODE_ENABLED     = 'template_synchronization_relist_mode_enabled';
    const INSTRUCTION_TYPE_RELIST_MODE_DISABLED    = 'template_synchronization_relist_mode_disabled';
    const INSTRUCTION_TYPE_RELIST_SETTINGS_CHANGED = 'template_synchronization_relist_settings_changed';

    const INSTRUCTION_TYPE_STOP_MODE_ENABLED       = 'template_synchronization_stop_mode_enabled';
    const INSTRUCTION_TYPE_STOP_MODE_DISABLED      = 'template_synchronization_stop_mode_disabled';
    const INSTRUCTION_TYPE_STOP_SETTINGS_CHANGED   = 'template_synchronization_stop_settings_changed';

    //########################################

    protected function getInstructionInitiator()
    {
        return self::INSTRUCTION_INITIATOR;
    }

    // ---------------------------------------

    protected function getInstructionsData(\Ess\M2ePro\Model\ActiveRecord\Diff $diff, $status)
    {
        /** @var \Ess\M2ePro\Model\Template\Synchronization\DiffAbstract $diff */

        $data = [];

        if ($diff->isListModeEnabled()) {
            $priority = 0;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_LIST_MODE_ENABLED,
                'priority' => $priority
            ];
        }

        if ($diff->isListModeDisabled()) {
            $priority = 0;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                $priority = 5;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_LIST_MODE_DISABLED,
                'priority' => $priority
            ];
        }

        if ($diff->isListSettingsChanged()) {
            $priority = 0;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED) {
                $priority = 20;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_LIST_SETTINGS_CHANGED,
                'priority' => $priority
            ];
        }

        if ($diff->isRelistModeEnabled()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD ||
                $status == \Ess\M2ePro\Model\Listing\Product::ACTION_STOP ||
                $status == \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED) {
                $priority = 50;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_RELIST_MODE_ENABLED,
                'priority' => $priority
            ];
        }

        if ($diff->isRelistModeDisabled()) {
            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_RELIST_MODE_DISABLED,
                'priority' => 5
            ];
        }

        if ($diff->isRelistSettingsChanged()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD ||
                $status == \Ess\M2ePro\Model\Listing\Product::ACTION_STOP ||
                $status == \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED) {
                $priority = 40;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_RELIST_SETTINGS_CHANGED,
                'priority' => $priority
            ];
        }

        if ($diff->isStopModeEnabled()) {
            $priority = 0;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_STOP_MODE_ENABLED,
                'priority' => $priority
            ];
        }

        if ($diff->isStopModeDisabled()) {
            $priority = 0;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 5;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_STOP_MODE_DISABLED,
                'priority' => $priority
            ];
        }

        if ($diff->isStopSettingsChanged()) {
            $priority = 0;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 20;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_STOP_SETTINGS_CHANGED,
                'priority' => $priority
            ];
        }

        return $data;
    }

    //########################################
}
