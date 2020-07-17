<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Category;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Category\ChangeProcessor
 */
class ChangeProcessor extends \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract
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
        /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Diff $diff */

        $data = [];

        if ($diff->isCategoriesDifferent()) {
            $data[] = [
                'type'      => self::INSTRUCTION_TYPE_CATEGORIES_DATA_CHANGED,
                'priority'  => $status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED ? 30 : 5,
            ];
        }

        return $data;
    }

    //########################################
}
