<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Custom;

use Ess\M2ePro\Model\Amazon\Listing\Product as AmazonListingProduct;

/**
 * Class \Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Custom\AmazonGeneralIdOwner
 */
class AmazonGeneralIdOwner extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'is_general_id_owner';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('ASIN/ISBN Creator');
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return int
     */
    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        return (int)$product->getData('is_general_id_owner');
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
                'value' => AmazonListingProduct::IS_GENERAL_ID_OWNER_YES,
                'label' => $helper->__('Yes'),
            ],
            [
                'value' => AmazonListingProduct::IS_GENERAL_ID_OWNER_NO,
                'label' => $helper->__('No'),
            ],
        ];
    }

    //########################################
}
