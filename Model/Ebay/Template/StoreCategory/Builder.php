<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\StoreCategory;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\StoreCategory\Builder
 */
class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    //########################################

    protected function prepareData()
    {
        $data = [];

        $keys = [
            'account_id',
            'category_mode',
            'category_id',
            'category_attribute',
            'category_path'
        ];

        foreach ($keys as $key) {
            isset($this->rawData[$key]) && $data[$key] = $this->rawData[$key];
        }

        return $data;
    }

    public function getDefaultData()
    {
        return [
            'category_id'        => 0,
            'category_path'      => '',
            'category_mode'      => \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_NONE,
            'category_attribute' => '',
        ];
    }

    //########################################
}
