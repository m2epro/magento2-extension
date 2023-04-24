<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Shipping;

class ChangeProcessor extends \Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\ChangeProcessorAbstract
{
    public const INSTRUCTION_INITIATOR = 'template_shipping_change_processor';

    /**
     * @return string
     */
    protected function getInstructionInitiator(): string
    {
        return self::INSTRUCTION_INITIATOR;
    }

    /**
     * @param \Ess\M2ePro\Model\ActiveRecord\Diff $diff
     * @param $status
     *
     * @return array
     */
    protected function getInstructionsData(\Ess\M2ePro\Model\ActiveRecord\Diff $diff, $status): array
    {
        /** @var \Ess\M2ePro\Model\Amazon\Template\Shipping\Diff $diff */

        $data = [];

        if ($diff->isDetailsDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type' => self::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        return $data;
    }
}
