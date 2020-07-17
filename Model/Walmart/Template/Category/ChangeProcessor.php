<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\Category;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\Category\ChangeProcessor
 */
class ChangeProcessor extends \Ess\M2ePro\Model\Walmart\Template\ChangeProcessor\ChangeProcessorAbstract
{
    const INSTRUCTION_INITIATOR = 'template_category_change_processor';

    //########################################

    protected function getInstructionInitiator()
    {
        return self::INSTRUCTION_INITIATOR;
    }

    // ---------------------------------------

    protected function getInstructionsData(\Ess\M2ePro\Model\ActiveRecord\Diff $diff, $status)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Template\Category\Diff $diff */

        $data = [];

        if ($diff->isDetailsDifferent()) {
            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
                'priority'  => 5,
            ];
        }

        return $data;
    }

    //########################################
}
