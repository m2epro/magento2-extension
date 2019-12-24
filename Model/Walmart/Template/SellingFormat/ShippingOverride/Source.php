<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride;

/**
 * Class \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride\Source
 */
class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var $magentoProduct \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = null;

    /**
     * @var \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride
     */
    private $sellingFormatShippingOverrideTemplateModel = null;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $this->magentoProduct = $magentoProduct;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function getMagentoProduct()
    {
        return $this->magentoProduct;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride $instance
     * @return $this
     */
    public function setSellingFormatShipingOverrideTemplate(
        \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride $instance
    ) {
        $this->sellingFormatShippingOverrideTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride
     */
    public function getSellingFormatShippingOverrideTemplate()
    {
        return $this->sellingFormatShippingOverrideTemplateModel;
    }

    //########################################

    /**
     * @return float
     */
    public function getCost()
    {
        $result = 0;

        switch ($this->getSellingFormatShippingOverrideTemplate()->getCostMode()) {
            case \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride::COST_MODE_FREE:
                $result = 0;
                break;
            case \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride::COST_MODE_CUSTOM_VALUE:
                $result = $this->getSellingFormatShippingOverrideTemplate()->getCostValue();
                break;
            case \Ess\M2ePro\Model\Walmart\Template\SellingFormat\ShippingOverride::COST_MODE_CUSTOM_ATTRIBUTE:
                $result = $this->getMagentoProduct()->getAttributeValue(
                    $this->getSellingFormatShippingOverrideTemplate()->getCostAttribute()
                );
                break;
        }

        is_string($result) && $result = str_replace(',', '.', $result);

        return round((float)$result, 2);
    }

    //########################################
}
