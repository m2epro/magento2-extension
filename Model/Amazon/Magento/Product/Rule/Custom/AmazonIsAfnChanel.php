<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Custom;

use Ess\M2ePro\Model\Amazon\Listing\Product as AmazonListingProduct;

class AmazonIsAfnChanel extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'amazon_is_afn_chanel';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Fulfillment');
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return int
     */
    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $isAfn = (int)$product->getData('is_afn_channel');
        $afnState = (int)$product->getData('variation_parent_afn_state');

        if (($this->filterOperator == '==' && $this->filterCondition == AmazonListingProduct::IS_AFN_CHANNEL_YES) ||
            ($this->filterOperator == '!=' && $this->filterCondition == AmazonListingProduct::IS_AFN_CHANNEL_NO)) {
            return $isAfn;
        }

        return (!$isAfn || $afnState == AmazonListingProduct::VARIATION_PARENT_IS_AFN_STATE_PARTIAL)
            ? AmazonListingProduct::IS_AFN_CHANNEL_NO
            : AmazonListingProduct::IS_AFN_CHANNEL_YES;
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
                'value' => AmazonListingProduct::IS_AFN_CHANNEL_NO,
                'label' => $helper->__('Merchant'),
            ),
            array(
                'value' => AmazonListingProduct::IS_AFN_CHANNEL_YES,
                'label' => $helper->__('Amazon'),
            ),
        );
    }

    //########################################
}