<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  2011-2017 ESS-UA [M2E Pro]
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\ProductTaxCode;

class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Magento\Product $magentoProduct
     */
    private $magentoProduct = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode $productTaxCodeTemplateModel
     */
    private $productTaxCodeTemplateModel = null;

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
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode $instance
     * @return $this
     */
    public function setProductTaxCodeTemplate(\Ess\M2ePro\Model\Amazon\Template\ProductTaxCode $instance)
    {
        $this->productTaxCodeTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode
     */
    public function getProductTaxCodeTemplate()
    {
        return $this->productTaxCodeTemplateModel;
    }

    //########################################

    /**
     * @return string
     */
    public function getProductTaxCode()
    {
        $result = '';

        switch ($this->getProductTaxCodeTemplate()->getProductTaxCodeMode()) {
            case \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode::PRODUCT_TAX_CODE_MODE_VALUE:
                $result = $this->getProductTaxCodeTemplate()->getProductTaxCodeValue();
                break;

            case \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode::PRODUCT_TAX_CODE_MODE_ATTRIBUTE:
                $result = $this->getMagentoProduct()->getAttributeValue(
                    $this->getProductTaxCodeTemplate()->getProductTaxCodeAttribute()
                );
                break;
        }

        return $result;
    }

    //########################################
}