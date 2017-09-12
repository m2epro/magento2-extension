<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Shipping\Service;

class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var $magentoProduct \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = null;

    /**
     * @var $shippingServiceTemplateModel \Ess\M2ePro\Model\Ebay\Template\Shipping\Service
     */
    private $shippingServiceTemplateModel = null;

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
     * @param \Ess\M2ePro\Model\Ebay\Template\Shipping\Service $instance
     * @return $this
     */
    public function setShippingServiceTemplate(\Ess\M2ePro\Model\Ebay\Template\Shipping\Service $instance)
    {
        $this->shippingServiceTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping\Service
     */
    public function getShippingServiceTemplate()
    {
        return $this->shippingServiceTemplateModel;
    }

    //########################################

    /**
     * @param null $storeForConvertingAttributeTypePrice
     * @return float
     */
    public function getCost($storeForConvertingAttributeTypePrice = NULL)
    {
        $result = 0;

        switch ($this->getShippingServiceTemplate()->getCostMode()) {
            case \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_FREE:
                $result = 0;
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_CUSTOM_VALUE:
                $result = $this->getShippingServiceTemplate()->getCostValue();
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_CUSTOM_ATTRIBUTE:
                $result = $this->getMagentoProductAttributeValue(
                    $this->getShippingServiceTemplate()->getCostValue(),
                    $storeForConvertingAttributeTypePrice
                );
                break;
        }

        is_string($result) && $result = str_replace(',','.',$result);

        return round((float)$result,2);
    }

    /**
     * @param null $storeForConvertingAttributeTypePrice
     * @return float
     */
    public function getCostAdditional($storeForConvertingAttributeTypePrice = NULL)
    {
        $result = 0;

        switch ($this->getShippingServiceTemplate()->getCostMode()) {
            case \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_FREE:
                $result = 0;
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_CUSTOM_VALUE:
                $result = $this->getShippingServiceTemplate()->getCostAdditionalValue();
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_CUSTOM_ATTRIBUTE:
                $result = $this->getMagentoProductAttributeValue(
                    $this->getShippingServiceTemplate()->getCostAdditionalValue(),
                    $storeForConvertingAttributeTypePrice
                );
                break;
        }

        is_string($result) && $result = str_replace(',','.',$result);

        return round((float)$result,2);
    }

    /**
     * @param null $storeForConvertingAttributeTypePrice
     * @return float
     */
    public function getCostSurcharge($storeForConvertingAttributeTypePrice = NULL)
    {
        $result = 0;

        switch ($this->getShippingServiceTemplate()->getCostMode()) {
            case \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_FREE:
                $result = 0;
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_CUSTOM_VALUE:
                $result = $this->getShippingServiceTemplate()->getCostSurchargeValue();
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Shipping\Service::COST_MODE_CUSTOM_ATTRIBUTE:
                $result = $this->getMagentoProductAttributeValue(
                    $this->getShippingServiceTemplate()->getCostSurchargeValue(),
                    $storeForConvertingAttributeTypePrice
                );
                break;
        }

        is_string($result) && $result = str_replace(',','.',$result);

        return round((float)$result,2);
    }

    // ---------------------------------------

    protected function getMagentoProductAttributeValue($attributeCode, $store)
    {
        if (is_null($store)) {
            return $this->getMagentoProduct()->getAttributeValue($attributeCode);
        }

        $currency = $this->getShippingServiceTemplate()
            ->getShippingTemplate()
            ->getMarketplace()
            ->getChildObject()
            ->getCurrency();

        return $this->getHelper('Magento\Attribute')->convertAttributeTypePriceFromStoreToMarketplace(
            $this->getMagentoProduct(),
            $attributeCode,
            $currency,
            $store
        );
    }

    //########################################
}