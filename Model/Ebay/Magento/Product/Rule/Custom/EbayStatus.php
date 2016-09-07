<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom;

class EbayStatus extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'ebay_status';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Status');
    }

    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        return $product->getData('ebay_status');
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
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
                'label' => $helper->__('Not Listed'),
            ),
            array(
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                'label' => $helper->__('Listed'),
            ),
            array(
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN,
                'label' => $helper->__('Listed (Hidden)'),
            ),
            array(
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD,
                'label' => $helper->__('Sold'),
            ),
            array(
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
                'label' => $helper->__('Stopped'),
            ),
            array(
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED,
                'label' => $helper->__('Finished'),
            ),
            array(
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
                'label' => $helper->__('Pending'),
            ),
        );
    }

    //########################################
}