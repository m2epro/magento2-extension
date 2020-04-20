<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Custom;

/**
 * Class \Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Custom\AmazonOnlinePrice
 */
class AmazonOnlinePrice extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'amazon_online_price';
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

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return array|mixed
     */
    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $minPrice = $product->getData('min_online_price');
        $maxPrice = $product->getData('max_online_price');

        if (!empty($minPrice) && !empty($maxPrice) && $minPrice != $maxPrice) {
            return [
                $product->getData('min_online_price'),
                $product->getData('max_online_price'),
            ];
        }

        return $product->getData('min_online_price');
    }

    //########################################
}
