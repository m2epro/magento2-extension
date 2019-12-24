<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Magento\Product\Rule\Custom;

/**
 * Class \Ess\M2ePro\Model\Walmart\Magento\Product\Rule\Custom\WalmartStartDate
 */
class WalmartStartDate extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'walmart_start_date';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Start Date');
    }

    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $date = $product->getData('online_start_date');
        if (empty($date)) {
            return null;
        }

        $date = new \DateTime($date);

        return strtotime($date->format('Y-m-d'));
    }

    /**
     * @return string
     */
    public function getInputType()
    {
        return 'date';
    }

    /**
     * @return string
     */
    public function getValueElementType()
    {
        return 'date';
    }

    //########################################
}
