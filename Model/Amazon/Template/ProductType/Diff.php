<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\ProductType;

class Diff extends \Ess\M2ePro\Model\ActiveRecord\Diff
{
    /**
     * @return bool
     */
    public function isDifferent()
    {
        $keys = ['settings', 'dictionary_product_type_id'];

        return $this->isSettingsDifferent($keys);
    }
}
