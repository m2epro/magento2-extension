<?php

namespace Ess\M2ePro\Model\Walmart\ProductType\Builder;

use Ess\M2ePro\Model\ResourceModel\Walmart\ProductType as ProductTypeResource;

class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    public function isDifferent()
    {
        return $this->isSettingsDifferent([
            ProductTypeResource::COLUMN_ATTRIBUTES_SETTINGS,
            ProductTypeResource::COLUMN_DICTIONARY_PRODUCT_TYPE_ID,
        ]);
    }
}
