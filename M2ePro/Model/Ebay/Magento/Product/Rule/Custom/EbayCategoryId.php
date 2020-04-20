<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom;

/**
 * Class \Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom\EbayCategoryId
 */
class EbayCategoryId extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'ebay_online_category';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Category ID');
    }

    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $onlineCategory = $product->getData('online_category');
        if (empty($onlineCategory)) {
            return null;
        }

        preg_match('/^.+\((\d+)\)$/x', $onlineCategory, $matches);

        if (empty($matches[1])) {
            return null;
        }

        return $matches[1];
    }

    //########################################
}
