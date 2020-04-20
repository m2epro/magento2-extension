<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Custom;

/**
 * Class \Ess\M2ePro\Model\Amazon\Magento\Product\Rule\Custom\AmazonGeneralIdState
 */
class AmazonGeneralIdState extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'amazon_general_id_state';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('ASIN/ISBN Status');
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return int
     */
    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $generalId = $product->getData('general_id');

        if (!empty($generalId)) {
            return \Ess\M2ePro\Model\Amazon\Listing\Product::GENERAL_ID_STATE_SET;
        }

        if ($product->getData('is_general_id_owner') == 1) {
            return \Ess\M2ePro\Model\Amazon\Listing\Product::GENERAL_ID_STATE_READY_FOR_NEW_ASIN;
        }

        $searchStatusActionRequired = \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_ACTION_REQUIRED;
        $searchStatusNotFound = \Ess\M2ePro\Model\Amazon\Listing\Product::SEARCH_SETTINGS_STATUS_NOT_FOUND;

        if ($product->getData('search_settings_status') == $searchStatusActionRequired ||
            $product->getData('search_settings_status') == $searchStatusNotFound) {
            return \Ess\M2ePro\Model\Amazon\Listing\Product::GENERAL_ID_STATE_ACTION_REQUIRED;
        }

        return \Ess\M2ePro\Model\Amazon\Listing\Product::GENERAL_ID_STATE_NOT_SET;
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
                'value' => \Ess\M2ePro\Model\Amazon\Listing\Product::GENERAL_ID_STATE_SET,
                'label' => $helper->__('Set'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Amazon\Listing\Product::GENERAL_ID_STATE_NOT_SET,
                'label' => $helper->__('Not Set'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Amazon\Listing\Product::GENERAL_ID_STATE_ACTION_REQUIRED,
                'label' => $helper->__('Action Required'),
            ],
            [
                'value' => \Ess\M2ePro\Model\Amazon\Listing\Product::GENERAL_ID_STATE_READY_FOR_NEW_ASIN,
                'label' => $helper->__('Ready for New ASIN/ISBN Creation'),
            ],
        ];
    }

    //########################################
}
