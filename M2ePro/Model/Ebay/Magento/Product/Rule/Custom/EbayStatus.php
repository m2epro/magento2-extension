<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom;

/**
 * Class \Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom\EbayStatus
 */
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
        return [
            [
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
                'label' => $helper->__('Not Listed'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                'label' => $helper->__('Listed'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN,
                'label' => $helper->__('Listed (Hidden)'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD,
                'label' => $helper->__('Sold'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
                'label' => $helper->__('Stopped'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED,
                'label' => $helper->__('Finished'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
                'label' => $helper->__('Pending'),
            ],
        ];
    }

    //########################################
}
