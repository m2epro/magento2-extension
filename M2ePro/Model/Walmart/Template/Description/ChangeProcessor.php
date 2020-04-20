<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\Description;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\Description\ChangeProcessor
 */
class ChangeProcessor extends \Ess\M2ePro\Model\Walmart\Template\ChangeProcessor\AbstractModel
{
    const INSTRUCTION_INITIATOR = 'template_description_change_processor';

    //########################################

    protected function getInstructionInitiator()
    {
        return self::INSTRUCTION_INITIATOR;
    }

    // ---------------------------------------

    protected function getInstructionsData(\Ess\M2ePro\Model\Template\Diff\AbstractModel $diff, $status)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Template\Description\Diff $diff */

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
