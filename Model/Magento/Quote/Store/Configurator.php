<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Quote\Store;

class Configurator extends \Ess\M2ePro\Model\AbstractModel
{
    protected $taxHelper;

    protected $quote;

    protected $proxyOrder;

    protected $taxConfig;

    protected $calculation;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Tax\Helper $taxHelper,
        \Magento\Tax\Model\Config $taxConfigModel,
        \Magento\Quote\Model\Quote $quote,
        \Ess\M2ePro\Model\Order\Proxy $proxyOrder,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Model\Calculation $calculation,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->taxHelper  = $taxHelper;
        $this->quote      = $quote;
        $this->proxyOrder = $proxyOrder;
        $this->taxConfig  = $taxConfig;
        $this->calculation = $calculation;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return array
     */
    public function getOriginalStoreConfig()
    {
        $keys = [
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_INCLUDES_TAX,
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_BASED_ON,
            \Magento\Customer\Model\GroupManagement::XML_PATH_DEFAULT_ID,
            $this->getOriginCountryIdXmlPath(),
            $this->getOriginRegionIdXmlPath(),
            $this->getOriginPostcodeXmlPath()
        ];

        $config = [];

        foreach ($keys as $key) {
            $config[$key] = $this->getStoreConfig($key);
        }

        return $config;
    }

    //########################################

    public function prepareStoreConfigForOrder()
    {
        // catalog prices
        // ---------------------------------------
        // reset flag, use store config instead
        $this->taxConfig->setPriceIncludesTax(true);
        $this->setStoreConfig(
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX, $this->isPriceIncludesTax()
        );
        // ---------------------------------------

        // shipping prices
        // ---------------------------------------
        $isShippingPriceIncludesTax = $this->isShippingPriceIncludesTax();
        if (method_exists($this->taxConfig, 'setShippingPriceIncludeTax')) {
            $this->taxConfig->setShippingPriceIncludeTax($isShippingPriceIncludesTax);
        } else {
            $this->setStoreConfig(
                \Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_INCLUDES_TAX, $isShippingPriceIncludesTax
            );
        }
        // ---------------------------------------

        // store origin address
        // ---------------------------------------
        $this->setStoreConfig($this->getOriginCountryIdXmlPath(), $this->getOriginCountryId());
        $this->setStoreConfig($this->getOriginRegionIdXmlPath(), $this->getOriginRegionId());
        $this->setStoreConfig($this->getOriginPostcodeXmlPath(), $this->getOriginPostcode());
        // ---------------------------------------

        // ---------------------------------------
        $this->setStoreConfig(
            \Magento\Customer\Model\GroupManagement::XML_PATH_DEFAULT_ID, $this->getDefaultCustomerGroupId()
        );
        $this->setStoreConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_BASED_ON, $this->getTaxCalculationBasedOn());
        // ---------------------------------------

        // store shipping tax class
        // ---------------------------------------
        $this->setStoreConfig(
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS, $this->getShippingTaxClassId()
        );
        // ---------------------------------------
    }

    //########################################

    private function isPriceIncludesTax()
    {
        if (!is_null($this->proxyOrder->isProductPriceIncludeTax())) {
            return $this->proxyOrder->isProductPriceIncludeTax();
        }

        return (bool)$this->getStoreConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX);
    }

    private function isShippingPriceIncludesTax()
    {
        if (!is_null($this->proxyOrder->isShippingPriceIncludeTax())) {
            return $this->proxyOrder->isShippingPriceIncludeTax();
        }

        return (bool)$this->getStoreConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_INCLUDES_TAX);
    }

    //########################################

    private function getShippingTaxClassId()
    {
        $proxyOrder = $this->proxyOrder;
        $hasRatesForCountry = $this->taxHelper->hasRatesForCountry($this->quote->getShippingAddress()->getCountryId());
        $storeShippingTaxRate = $this->taxHelper->getStoreShippingTaxRate($this->getStore());
        $calculationBasedOnOrigin = $this->taxHelper->isCalculationBasedOnOrigin($this->getStore());
        $shippingPriceTaxRate = $proxyOrder->getShippingPriceTaxRate();

        $isTaxSourceChannel = $proxyOrder->isTaxModeChannel()
            || ($proxyOrder->isTaxModeMixed() && $shippingPriceTaxRate > 0);

        if ($proxyOrder->isTaxModeNone()
            || ($isTaxSourceChannel && $shippingPriceTaxRate <= 0)
            || ($proxyOrder->isTaxModeMagento() && !$hasRatesForCountry && !$calculationBasedOnOrigin)
        ) {
            return \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE;
        }

        if ($proxyOrder->isTaxModeMagento()
            || $proxyOrder->getShippingPriceTaxRate() <= 0
            || $shippingPriceTaxRate == $storeShippingTaxRate
        ) {
            return $this->taxConfig->getShippingTaxClass($this->getStore());
        }

        // Create tax rule according to channel tax rate
        // ---------------------------------------
        /** @var $taxRuleBuilder \Ess\M2ePro\Model\Magento\Tax\Rule\Builder */
        $taxRuleBuilder = $this->modelFactory->getObject('Magento\Tax\Rule\Builder');
        $taxRuleBuilder->buildTaxRule(
            $shippingPriceTaxRate,
            $this->quote->getShippingAddress()->getCountryId(),
            $this->quote->getCustomerTaxClassId()
        );

        $taxRule = $taxRuleBuilder->getRule();
        $productTaxClasses = $taxRule->getProductTaxClasses();
        // ---------------------------------------

        return array_shift($productTaxClasses);
    }

    //########################################

    private function getOriginCountryId()
    {
        $originCountryId = $this->getStoreConfig($this->getOriginCountryIdXmlPath());

        if ($this->proxyOrder->isTaxModeMagento()) {
            return $originCountryId;
        }

        if ($this->proxyOrder->isTaxModeMixed() && !$this->proxyOrder->hasTax()) {
            return $originCountryId;
        }

        if ($this->proxyOrder->isTaxModeNone()
            || ($this->proxyOrder->isTaxModeChannel() && !$this->proxyOrder->hasTax())
        ) {
            return '';
        }

        return $this->quote->getShippingAddress()->getCountryId();
    }

    private function getOriginRegionId()
    {
        $originRegionId = $this->getStoreConfig($this->getOriginRegionIdXmlPath());

        if ($this->proxyOrder->isTaxModeMagento()) {
            return $originRegionId;
        }

        if ($this->proxyOrder->isTaxModeMixed() && !$this->proxyOrder->hasTax()) {
            return $originRegionId;
        }

        if ($this->proxyOrder->isTaxModeNone()
            || ($this->proxyOrder->isTaxModeChannel() && !$this->proxyOrder->hasTax())
        ) {
            return '';
        }

        return $this->quote->getShippingAddress()->getRegionId();
    }

    private function getOriginPostcode()
    {
        $originPostcode = $this->getStoreConfig($this->getOriginPostcodeXmlPath());

        if ($this->proxyOrder->isTaxModeMagento()) {
            return $originPostcode;
        }

        if ($this->proxyOrder->isTaxModeMixed() && !$this->proxyOrder->hasTax()) {
            return $originPostcode;
        }

        if ($this->proxyOrder->isTaxModeNone()
            || ($this->proxyOrder->isTaxModeChannel() && !$this->proxyOrder->hasTax())
        ) {
            return '';
        }

        return $this->quote->getShippingAddress()->getPostcode();
    }

    //########################################

    private function getDefaultCustomerGroupId()
    {
        $defaultCustomerGroupId = $this->getStoreConfig(\Magento\Customer\Model\GroupManagement::XML_PATH_DEFAULT_ID);

        if ($this->proxyOrder->isTaxModeMagento()) {
            return $defaultCustomerGroupId;
        }

        $currentCustomerTaxClass = $this->calculation->getDefaultCustomerTaxClass($this->getStore());
        $quoteCustomerTaxClass = $this->quote->getCustomerTaxClassId();

        if ($currentCustomerTaxClass == $quoteCustomerTaxClass) {
            return $defaultCustomerGroupId;
        }

        //TODO
        // ugliest hack ever!
        // we have to remove exist singleton instance from the Mage registry
        // because Mage\Tax\Model\Calculation::getDefaultCustomerTaxClass() method stores the customer tax class
        // after the first call in protected variable and then it doesn't care what store was given to it
//        Mage::unregister('_singleton/tax/calculation');

        // default customer tax class depends on default customer group
        // so we override store setting for this with the customer group from the quote
        // this is done to make store & address tax requests equal
        return $this->quote->getCustomerGroupId();
    }

    //########################################

    private function getTaxCalculationBasedOn()
    {
        $basedOn = $this->getStoreConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_BASED_ON);

        if ($this->proxyOrder->isTaxModeMagento()) {
            return $basedOn;
        }

        if ($this->proxyOrder->isTaxModeMixed() && !$this->proxyOrder->hasTax()) {
            return $basedOn;
        }

        return 'shipping';
    }

    //########################################

    private function getOriginCountryIdXmlPath()
    {
        return \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_COUNTRY_ID;
    }

    private function getOriginRegionIdXmlPath()
    {
        return \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_REGION_ID;
    }

    private function getOriginPostcodeXmlPath()
    {
        return \Magento\Shipping\Model\Config::XML_PATH_ORIGIN_POSTCODE;
    }

    //########################################

    private function getStore()
    {
        return $this->quote->getStore();
    }

    // ---------------------------------------

    private function setStoreConfig($key, $value)
    {
        $this->getStore()->setConfig($key, $value);
    }

    private function getStoreConfig($key)
    {
        return $this->getStore()->getConfig($key);
    }

    //########################################
}