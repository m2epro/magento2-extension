<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Shipping;

/**
 * Class \Ess\M2ePro\Model\Amazon\Template\Shipping\Diff
 */
class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    //########################################

    public function isDifferent()
    {
        return $this->isDetailsDifferent();
    }

    //########################################

    public function isDetailsDifferent()
    {
        $keys = [
            'template_name_mode',
            'template_name_value',
            'template_name_attribute',
        ];

        return $this->isSettingsDifferent($keys);
    }

    //########################################
}
