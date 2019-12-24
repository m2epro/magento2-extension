<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom;

/**
 * Class \Ess\M2ePro\Model\Ebay\Magento\Product\Rule\Custom\EbaySoldQty
 */
class EbaySoldQty extends \Ess\M2ePro\Model\Magento\Product\Rule\Custom\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'ebay_sold_qty';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->helperFactory->getObject('Module\Translation')->__('Sold QTY');
    }

    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        return $product->getData('online_qty_sold');
    }

    //########################################
}
