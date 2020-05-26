<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Category;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Category\Diff
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
            'category_main_id',
            'category_main_mode',
            'category_main_path',
            'category_main_attribute',
            'specifics'
        ];

        return $this->isSettingsDifferent($keys);
    }

    //########################################
}
