<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Account\Repricing;

/**
 * Class \Ess\M2ePro\Model\Amazon\Account\Repricing\ChangeProcessor
 */
class ChangeProcessor extends \Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\ChangeProcessorAbstract
{
    const INSTRUCTION_INITIATOR = 'account_repricing_change_processor';

    const INSTRUCTION_TYPE_ACCOUNT_REPRICING_DATA_CHANGED = 'account_repricing_data_changed';

    //########################################

    protected function getInstructionInitiator()
    {
        return self::INSTRUCTION_INITIATOR;
    }

    // ---------------------------------------

    protected function getInstructionsData(\Ess\M2ePro\Model\ActiveRecord\Diff $diff, $status)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\Diff $diff */

        $data = [];

        if ($diff->isRepricingDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type'     => self::INSTRUCTION_TYPE_ACCOUNT_REPRICING_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        return $data;
    }

    //########################################
}
