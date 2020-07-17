<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\SellingFormat;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ChangeProcessor
 */
class ChangeProcessor extends \Ess\M2ePro\Model\Walmart\Template\ChangeProcessor\ChangeProcessorAbstract
{
    const INSTRUCTION_INITIATOR = 'template_selling_format_change_processor';

    //########################################

    protected function getInstructionInitiator()
    {
        return self::INSTRUCTION_INITIATOR;
    }

    // ---------------------------------------

    protected function getInstructionsData(\Ess\M2ePro\Model\ActiveRecord\Diff $diff, $status)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat\Diff $diff */

        $data = [];

        if ($diff->isQtyDifferent()) {
            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_QTY_DATA_CHANGED,
                'priority'  => 80,
            ];
        }

        if ($diff->isLagTimeDifferent()) {
            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_LAG_TIME_DATA_CHANGED,
                'priority'  => 60,
            ];
        }

        if ($diff->isPriceDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 60;
            }

            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_PRICE_DATA_CHANGED,
                'priority'  => $priority,
            ];
        }

        if ($diff->isPromotionsDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 50;
            }

            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_PROMOTIONS_DATA_CHANGED,
                'priority'  => $priority,
            ];
        }

        if ($diff->isDetailsDifferent()) {
            $priority = 5;

            if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                $priority = 30;
            }

            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
                'priority'  => $priority,
            ];
        }

        return $data;
    }

    //########################################
}
