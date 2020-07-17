<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\ReturnPolicy;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy\Diff
 */
class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    //########################################

    public function isDifferent()
    {
        return $this->isReturnDifferent();
    }

    //########################################

    public function isReturnDifferent()
    {
        $keys = [
            'accepted',
            'option',
            'within',
            'shipping_cost',

            'international_accepted',
            'international_option',
            'international_within',
            'international_shipping_cost',

            'description',
        ];

        return $this->isSettingsDifferent($keys);
    }

    //########################################
}
