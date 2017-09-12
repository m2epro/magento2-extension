<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service;

class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var $magentoProduct \Ess\M2ePro\Model\Magento\Product
     */
    private $magentoProduct = null;

    /**
     * @var $shippingOverrideServiceTemplateModel \Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service
     */
    private $shippingOverrideServiceTemplateModel = null;

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
     * @param \Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service $instance
     * @return $this
     */
    public function setShippingOverrideServiceTemplate(
        \Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service $instance)
    {
        $this->shippingOverrideServiceTemplateModel = $instance;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service
     */
    public function getShippingOverrideServiceTemplate()
    {
        return $this->shippingOverrideServiceTemplateModel;
    }

    //########################################

    /**
     * @param $storeForConvertingAttributeTypePrice
     * @return float
     */
    public function getCost($storeForConvertingAttributeTypePrice = NULL)
    {
        $result = 0;

        switch ($this->getShippingOverrideServiceTemplate()->getCostMode()) {
            case\Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service::COST_MODE_FREE:
                $result = 0;
                break;
            case\Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service::COST_MODE_CUSTOM_VALUE:
                $result = $this->getShippingOverrideServiceTemplate()->getCostValue();
                break;
            case\Ess\M2ePro\Model\Amazon\Template\ShippingOverride\Service::COST_MODE_CUSTOM_ATTRIBUTE:
                $result = $this->getMagentoProductAttributeValue(
                    $this->getShippingOverrideServiceTemplate()->getCostValue(),
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
        $attributeValue = $this->getMagentoProduct()->getAttributeValue($attributeCode);

        if (empty($attributeValue) || is_null($store)) {
            return $attributeValue;
        }

        $isPriceConvertEnabled = $this->getHelper('Module')->getConfig()->getGroupValue(
            '/magento/attribute/', 'price_type_converting'
        );

        if ($isPriceConvertEnabled &&
            $this->getHelper('Magento\Attribute')->isAttributeInputTypePrice($attributeCode)) {

            $currency = $this->getShippingOverrideServiceTemplate()
                ->getShippingOverrideTemplate()
                ->getMarketplace()
                ->getChildObject()
                ->getCurrency();

            return $this->modelFactory->getObject('Currency')->convertPrice($attributeValue, $currency, $store);
        }

        return $attributeValue;
    }

    //########################################
}