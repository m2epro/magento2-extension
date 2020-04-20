<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\OtherCategory;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\OtherCategory\Diff
 */
class Diff extends \Ess\M2ePro\Model\Template\Diff\AbstractModel
{
    //########################################

    public function isDifferent()
    {
        return $this->isCategoriesDifferent();
    }

    //########################################

    public function isCategoriesDifferent()
    {
        $keys = [
            'category_secondary_mode',
            'category_secondary_id',
            'category_secondary_path',
            'category_secondary_attribute',
            'store_category_main_mode',
            'store_category_main_id',
            'store_category_main_path',
            'store_category_main_attribute',
            'store_category_secondary_mode',
            'store_category_secondary_id',
            'store_category_secondary_path',
            'store_category_secondary_attribute',
        ];

        return $this->isSettingsDifferent($keys);
    }

    //########################################
}
