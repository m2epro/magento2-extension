<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Account\Repricing;

/**
 * Class \Ess\M2ePro\Model\Amazon\Account\Repricing\Diff
 */
class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    //########################################

    public function isDifferent()
    {
        return $this->isRepricingDifferent();
    }

    //########################################

    public function isRepricingDifferent()
    {
        $keys = [
            'regular_price_mode',
            'regular_price_coefficient',
            'regular_price_attribute',
            'regular_price_variation_mode',
            'min_price_mode',
            'min_price_coefficient',
            'min_price_attribute',
            'min_price_value',
            'min_price_percent',
            'min_price_variation_mode',
            'max_price_mode',
            'max_price_coefficient',
            'max_price_attribute',
            'max_price_value',
            'max_price_percent',
            'max_price_variation_mode',
            'disable_mode',
            'disable_mode_attribute',
        ];

        return $this->isSettingsDifferent($keys);
    }

    //########################################
}
