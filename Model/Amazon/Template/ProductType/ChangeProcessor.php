<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\ProductType;

class ChangeProcessor extends \Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\ChangeProcessorAbstract
{
    public const INSTRUCTION_INITIATOR = 'template_product_type_change_processor';

    /**
     * @return string
     */
    protected function getInstructionInitiator()
    {
        return self::INSTRUCTION_INITIATOR;
    }

    /**
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductType\Diff $diff
     * @param $status
     *
     * @return array
     */
    protected function getInstructionsData(\Ess\M2ePro\Model\ActiveRecord\Diff $diff, $status)
    {
        $data[] = [
            'type'     => self::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
            'priority' => 50,
        ];

        return $data;
    }
}
