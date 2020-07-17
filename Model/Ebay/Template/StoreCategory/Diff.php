<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\StoreCategory;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\StoreCategory\Diff
 */
class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
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
            'category_mode',
            'category_id',
            'category_path',
            'category_attribute'
        ];

        return $this->isSettingsDifferent($keys);
    }

    //########################################
}
