<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom;

class EbayPrice extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'ebay_online_current_price';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Price');
    }

    /**
     * @return string
     */
    public function getInputType()
    {
        return 'price';
    }

    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $minPrice = $product->getData('min_online_price');
        $maxPrice = $product->getData('max_online_price');

        if (!empty($minPrice) && !empty($maxPrice) && $minPrice != $maxPrice) {
            return array(
                $product->getData('min_online_price'),
                $product->getData('max_online_price'),
            );
        }

        return $product->getData('min_online_price');
    }

    //########################################
}