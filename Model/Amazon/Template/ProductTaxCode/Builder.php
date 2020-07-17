<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\ProductTaxCode;

/**
 * Class Ess\M2ePro\Model\Amazon\Template\ProductTaxCode\Builder
 */
class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    //########################################

    protected function prepareData()
    {
        $data = [];

        $keys = array_keys($this->getDefaultData());

        foreach ($keys as $key) {
            if (isset($this->rawData[$key])) {
                $data[$key] = $this->rawData[$key];
            }
        }

        return $data;
    }

    public function getDefaultData()
    {
        return [
            'title' => '',

            'product_tax_code_mode'      => '',
            'product_tax_code_value'     => '',
            'product_tax_code_attribute' => '',
        ];
    }

    //########################################
}
