<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Template\Repricer;

class ChangeProcessor extends \Ess\M2ePro\Model\Walmart\Template\ChangeProcessor\ChangeProcessorAbstract
{
    public const INSTRUCTION_INITIATOR = 'template_repricer_change_processor';

    protected function getInstructionInitiator(): string
    {
        return self::INSTRUCTION_INITIATOR;
    }

    /**
     * @param \Ess\M2ePro\Model\Walmart\Template\Repricer\Diff  $diff
     * @param $status
     *
     * @return array
     */
    protected function getInstructionsData(\Ess\M2ePro\Model\ActiveRecord\Diff $diff, $status): array
    {
        $data = [];

        if ($diff->isRepricerDifferent()) {
            $priority = 5;
            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 50;
            }

            $data[] = [
                'type' => self::INSTRUCTION_TYPE_REPRICER_DATA_CHANGED,
                'priority' => $priority,
            ];
        }

        return $data;
    }
}
