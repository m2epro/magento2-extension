<?php

namespace Ess\M2ePro\Model\Walmart\ProductType\Builder;

class ChangeProcessor extends \Ess\M2ePro\Model\Walmart\Template\ChangeProcessor\ChangeProcessorAbstract
{
    public const INSTRUCTION_INITIATOR = 'product_type_change_processor';

    protected function getInstructionInitiator()
    {
        return self::INSTRUCTION_INITIATOR;
    }

    protected function getInstructionsData(\Ess\M2ePro\Model\ActiveRecord\Diff $diff, $status)
    {
        $data[] = [
            'type' => self::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
            'priority' => 50,
        ];

        return $data;
    }
}
