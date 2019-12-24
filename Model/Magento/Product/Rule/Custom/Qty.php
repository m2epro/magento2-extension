<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product\Rule\Custom;

/**
 * Class \Ess\M2ePro\Model\Magento\Product\Rule\Custom\Qty
 */
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
     * - MSI engine v. 2.3.2: Index tables have correct QTY for all product except Bundle
     * - Regular engine: Index table has 0 QTY for complex products (bundle, configurable, grouped)
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return float
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getValueByProductInstance(\Magento\Catalog\Model\Product $product)
    {
        $magentoProduct = $this->modelFactory->getObject('Magento\Product');
        $magentoProduct->setProduct($product);

        if ($this->getHelper('Magento')->isMSISupportingVersion()) {
            return $magentoProduct->isBundleType() ? 0 : $magentoProduct->getQty();
        }

        if ($magentoProduct->isBundleType() ||
            $magentoProduct->isConfigurableType() ||
            $magentoProduct->isGroupedType()
        ) {
            return 0;
        }

        return $magentoProduct->getQty();
    }

    //########################################
}
