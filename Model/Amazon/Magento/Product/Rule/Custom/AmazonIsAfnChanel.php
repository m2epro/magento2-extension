<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Custom;

class AmazonIsAfnChanel
    extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
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
        return $product->getData('is_afn_channel');
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
                'value' => 0,
                'label' => $helper->__('Merchant'),
            ),
            array(
                'value' => 1,
                'label' => $helper->__('Amazon'),
            ),
        );
    }

    //########################################
}