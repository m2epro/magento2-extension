<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Payment;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Payment\Diff
 */
class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    //########################################

    public function isDifferent()
    {
        return $this->isPaymentDifferent();
    }

    //########################################

    public function isPaymentDifferent()
    {
        $keys = [
            'pay_pal_mode',
            'pay_pal_email_address',
            'pay_pal_immediate_payment',
            'services',
        ];

        return $this->isSettingsDifferent($keys);
    }

    //########################################
}
