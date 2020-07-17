<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\Category;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\Category\Diff
 */
class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    //########################################

    public function isDifferent()
    {
        return $this->isDetailsDifferent();
    }

    public function isDetailsDifferent()
    {
        $mainKeys = [
            'browsenode_id',
            'product_data_nick',
            'specifics'
        ];

        return $this->isSettingsDifferent($mainKeys);
    }

    //########################################
}
