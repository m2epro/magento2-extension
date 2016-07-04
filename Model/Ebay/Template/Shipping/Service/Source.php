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
     * @return float
     */
    public function getCost()
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
                $result = $this->getMagentoProduct()->getAttributeValue(
                    $this->getShippingServiceTemplate()->getCostValue()
                );
                break;
        }

        is_string($result) && $result = str_replace(',','.',$result);

        return round((float)$result,2);
    }

    /**
     * @return float
     */
    public function getCostAdditional()
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
                $result = $this->getMagentoProduct()->getAttributeValue(
                    $this->getShippingServiceTemplate()->getCostAdditionalValue()
                );
                break;
        }

        is_string($result) && $result = str_replace(',','.',$result);

        return round((float)$result,2);
    }

    /**
     * @return float
     */
    public function getCostSurcharge()
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
                $result = $this->getMagentoProduct()->getAttributeValue(
                    $this->getShippingServiceTemplate()->getCostSurchargeValue()
                );
                break;
        }

        is_string($result) && $result = str_replace(',','.',$result);

        return round((float)$result,2);
    }

    //########################################
}