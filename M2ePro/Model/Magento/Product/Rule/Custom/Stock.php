<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\Rule\Custom;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Rule\Custom\Stock
 */
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

    /**
     * - MSI engine v. 2.3.2: Index tables have correct salable status
     * - Regular engine: Index table has status with no applied "Manage Stock" setting
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return int
     */
    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProduct($product);

        return $this->getHelper('Magento')->isMSISupportingVersion()
            ? (int)$magentoProduct->isStockAvailability()
            : (int)$magentoProduct->getStockItem()->getDataByKey('is_in_stock');
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
        return [
            [
                'value' => 1,
                'label' => __('In Stock')
            ],
            [
                'value' => 0,
                'label' => __('Out Of Stock')
            ],
        ];
    }

    //########################################
}
