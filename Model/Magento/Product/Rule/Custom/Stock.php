<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\Rule\Custom;

class Stock extends AbstractModel
{
    //########################################

    /**
     * @return string
     */
    public function getAttributeCode()
    {
        return 'is_in_stock';
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->getHelper('Module\Translation')->__('Stock Availability');
    }

    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $stockItem = $this->stockItemFactory->create();
        $stockItem->getResource()->loadByProductId(
            $stockItem, $product->getId(), $stockItem->getStockId()
        );

        return $stockItem->getIsInStock();
    }

    //########################################

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
        return array(
            array(
                'value' => 1,
                'label' => __('In Stock')
            ),
            array(
                'value' => 0,
                'label' => __('Out Of Stock')
            ),
        );
    }

    //########################################
}