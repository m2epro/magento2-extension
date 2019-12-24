<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Magento\Product;

/**
 * Class \Ess\M2ePro\Model\Walmart\Magento\Product\Rule
 */
class Rule extends \Ess\M2ePro\Model\Magento\Product\Rule
{
    //########################################

    /**
     * @return string
     */
    public function getConditionClassName()
    {
        return 'Walmart_Magento_Product_Rule_Condition_Combine';
    }

    //########################################
}
