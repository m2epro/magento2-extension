<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Custom;

class AmazonStatus extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'amazon_status';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Status');
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return mixed
     */
    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $status = $product->getData('amazon_status');
        $variationChildStatuses = $product->getData('variation_child_statuses');

        if ($product->getData('is_variation_parent') && !empty($variationChildStatuses)) {
            $status = $this->getHelper('Data')->jsonDecode($variationChildStatuses);
        }

        return $status;
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
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN,
                'label' => $helper->__('Unknown'),
            ),
            array(
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
                'label' => $helper->__('Not Listed'),
            ),
            array(
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED,
                'label' => $helper->__('Active'),
            ),
            array(
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
                'label' => $helper->__('Inactive'),
            ),
            array(
                'value' => \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED,
                'label' => $helper->__('Inactive (Blocked)'),
            ),
        );
    }

    //########################################
}