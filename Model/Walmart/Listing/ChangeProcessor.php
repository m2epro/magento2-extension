<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Listing;

use Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction as InstructionResource;

class ChangeProcessor extends \Ess\M2ePro\Model\Template\ChangeProcessorAbstract
{
    public const INSTRUCTION_TYPE_CONDITION_DATA_CHANGED = 'listing_condition_data_changed';
    private const INSTRUCTION_INITIATOR = 'listing_change_processor';

    protected function getInstructionInitiator()
    {
        return self::INSTRUCTION_INITIATOR;
    }

    /**
     * @param Diff $diff
     */
    protected function getInstructionsData(\Ess\M2ePro\Model\ActiveRecord\Diff $diff, $status): array
    {
        $data = [];

        if ($diff->isConditionDifferent()) {
            $priority = $status == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED
                ? 10
                : 5;

            $data[] = [
                InstructionResource::COLUMN_TYPE => self::INSTRUCTION_TYPE_CONDITION_DATA_CHANGED,
                InstructionResource::COLUMN_PRIORITY => $priority,
            ];
        }

        return $data;
    }
}
