<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\Rule\Custom;

class Qty extends AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'qty';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->getHelper('Module\Translation')->__('QTY');
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return float
     */
    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        return $this->getStockItemByProductInstance($product)->getQty();
    }

    //########################################
}