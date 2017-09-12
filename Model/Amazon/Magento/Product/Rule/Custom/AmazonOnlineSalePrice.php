<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Custom;

class AmazonOnlineSalePrice extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'amazon_online_sale_price';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Sale Price');
    }

    /**
     * @return string
     */
    public function getInputType()
    {
        return 'price';
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return mixed
     */
    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        return $product->getData('online_regular_sale_price');
    }

    //########################################
}