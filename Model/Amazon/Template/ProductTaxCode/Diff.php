<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\ProductTaxCode;

/**
 * Class \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode\Diff
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
            'product_tax_code_mode',
            'product_tax_code_value',
            'product_tax_code_attribute',
        ];

        return $this->isSettingsDifferent($keys);
    }

    //########################################
}
