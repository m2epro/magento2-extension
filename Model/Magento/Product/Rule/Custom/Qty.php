<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
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

    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $stockItem = $this->stockItemFactory->create();
        $stockItem->getResource()->loadByProductId(
            $stockItem, $product->getId(), $stockItem->getStockId()
        );

        return $stockItem->getQty();
    }

    //########################################
}