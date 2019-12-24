<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Magento\Product\Rule\Custom;

/**
 * Class \Ess\M2ePro\Model\Walmart\Magento\Product\Rule\Custom\WalmartDetailsDataChanged
 */
class WalmartDetailsDataChanged extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'walmart_details_data_changed';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Item Details need to be updated');
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return mixed
     */
    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        return $product->getData('is_details_data_changed');
    }

    /**
     * @return string
     */
    public function getInputType()
    {
        return 'select';
    }

    /**
     * @return string
     */
    public function getValueElementType()
    {
        return 'select';
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return [
            [
                'value' => 0,
                'label' => $this->helperFactory->getObject('Module\Translation')->__('No'),
            ],
            [
                'value' => 1,
                'label' => $this->helperFactory->getObject('Module\Translation')->__('Yes'),
            ],
        ];
    }

    //########################################
}
