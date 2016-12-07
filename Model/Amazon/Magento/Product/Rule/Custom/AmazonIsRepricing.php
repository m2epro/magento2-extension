<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Custom;

use Ess\M2ePro\Model\Amazon\Listing\Product as AmazonListingProduct;

class AmazonIsRepricing extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'amazon_is_repricing';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('On Repricing');
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return int
     */
    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $isRepricing = (bool)(int)$product->getData('is_repricing');
        $haveEnabledChildProducts  = (bool)(int)$product->getData('variation_repricing_enabled_count');
        $haveDisabledChildProducts = (bool)(int)$product->getData('variation_repricing_disabled_count');

        return ($isRepricing || $haveEnabledChildProducts || $haveDisabledChildProducts)
            ? AmazonListingProduct::IS_REPRICING_YES
            : AmazonListingProduct::IS_REPRICING_NO;
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
        $helper = $this->helperFactory->getObject('Module\Translation');
        return array(
            array(
                'value' => AmazonListingProduct::IS_REPRICING_NO,
                'label' => $helper->__('No'),
            ),
            array(
                'value' => AmazonListingProduct::IS_REPRICING_YES,
                'label' => $helper->__('Yes'),
            ),
        );
    }

    //########################################
}