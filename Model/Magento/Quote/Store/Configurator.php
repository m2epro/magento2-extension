<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Quote\Store;

/**
 * Class \Ess\M2ePro\Model\Magento\Quote\Store\Configurator
 */
class Configurator extends \Ess\M2ePro\Model\AbstractModel
{
    protected $taxHelper;

    protected $storeConfig;

    protected $quote;

    protected $proxyOrder;

    protected $taxConfig;

    protected $calculation;

    protected $mutableConfig;

    protected $storeManager;

    //----------------------------------------

    /** @var \Magento\Store\Api\Data\StoreInterface */
    protected $originalStore;

    /** @var array  */
    protected $originalStoreConfig = [];

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Config\Mutable $mutableConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Model\Magento\Tax\Helper $taxHelper,
        \Magento\Framework\App\Config\ReinitableConfigInterface $storeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Quote\Model\Quote $quote,
        \Ess\M2ePro\Model\Order\ProxyObject $proxyOrder,
        \Magento\Tax\Model\Config $taxConfig,
        \Magento\Tax\Model\Calculation $calculation,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->mutableConfig = $mutableConfig;
        $this->taxHelper     = $taxHelper;
        $this->storeConfig   = $storeConfig;
        $this->quote         = $quote;
        $this->proxyOrder    = $proxyOrder;
        $this->taxConfig     = $taxConfig;
        $this->storeManager  = $storeManager;

        // We need to use newly created instances, because magento caches tax rates in private properties
        $this->calculation = $objectManager->create(\Magento\Tax\Model\Calculation::class, [
            'resource' => $objectManager->create($calculation->getResourceName())
        ]);

        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function prepareStoreConfigForOrder()
    {
        $this->saveOriginalStoreConfig();
        $this->helperFactory->getObject('Data\GlobalData')->setValue('use_mutable_config', true);

        // catalog prices
        // ---------------------------------------
        $isProductPriceIncludesTax = $this->isPriceIncludesTax();
        $this->taxConfig->setPriceIncludesTax($isProductPriceIncludesTax);
        $this->setStoreConfig(
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
            $isProductPriceIncludesTax
        );
        // ---------------------------------------

        // shipping prices
        // ---------------------------------------
        $isShippingPriceIncludesTax = $this->isShippingPriceIncludesTax();
        $this->taxConfig->setShippingPriceIncludeTax($isShippingPriceIncludesTax);
        $this->setStoreConfig(
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_INCLUDES_TAX,
            $isShippingPriceIncludesTax
        );
        // ---------------------------------------

        // Fixed Product Tax settings
        // ---------------------------------------
        if ($this->proxyOrder->isTaxModeChannel() ||
            ($this->proxyOrder->isTaxModeMixed() &&
                ($this->proxyOrder->hasTax() || $this->proxyOrder->getWasteRecyclingFee()))
        ) {
            $this->setStoreConfig(\Magento\Weee\Model\Config::XML_PATH_FPT_ENABLED, false);
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
            \Magento\Customer\Model\GroupManagement::XML_PATH_DEFAULT_ID,
            $this->getDefaultCustomerGroupId()
        );
        $this->setStoreConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_BASED_ON, $this->getTaxCalculationBasedOn());
        // ---------------------------------------

        // store shipping tax class
        // ---------------------------------------
        $this->setStoreConfig(
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
            $this->getShippingTaxClassId()
        );
        // ---------------------------------------

        /**
         * vendor/magento/module-quote/Model/Quote/Address.php::requestShippingRates()
         * Store is now not being taken from Quote
         */
        $this->storeManager->setCurrentStore($this->getStore()->getId());
    }

    public function restoreOriginalStoreConfigForOrder()
    {
        $this->helperFactory->getObject('Data\GlobalData')->unsetValue('use_mutable_config');
        foreach ($this->originalStoreConfig as $key => $value) {
            $this->mutableConfig->unsetValue(
                $key,
                $value,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->getStore()->getCode()
            );
        }

        $this->storeManager->setCurrentStore($this->originalStore->getId());
    }

    //########################################

    private function saveOriginalStoreConfig()
    {
        $keys = [
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX,
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_INCLUDES_TAX,
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_TAX_CLASS,
            \Magento\Tax\Model\Config::CONFIG_XML_PATH_BASED_ON,
            \Magento\Customer\Model\GroupManagement::XML_PATH_DEFAULT_ID,
            \Magento\Weee\Model\Config::XML_PATH_FPT_ENABLED,
            $this->getOriginCountryIdXmlPath(),
            $this->getOriginRegionIdXmlPath(),
            $this->getOriginPostcodeXmlPath()
        ];

        $this->originalStoreConfig = [];

        foreach ($keys as $key) {
            $this->originalStoreConfig[$key] = $this->getStoreConfig($key);
        }

        $this->originalStore = $this->storeManager->getStore();
    }

    //########################################

    public function isPriceIncludesTax()
    {
        if ($this->proxyOrder->isProductPriceIncludeTax() !== null) {
            return $this->proxyOrder->isProductPriceIncludeTax();
        }

        return (bool)$this->getStoreConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX);
    }

    public function isShippingPriceIncludesTax()
    {
        if ($this->proxyOrder->isShippingPriceIncludeTax() !== null) {
            return $this->proxyOrder->isShippingPriceIncludeTax();
        }

        return (bool)$this->getStoreConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_SHIPPING_INCLUDES_TAX);
    }

    //########################################

    public function getShippingTaxClassId()
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
        $taxRuleBuilder = $this->modelFactory->getObject('Magento_Tax_Rule_Builder');
        $taxRuleBuilder->buildShippingTaxRule(
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

        // default customer tax class depends on default customer group
        // so we override store setting for this with the customer group from the quote
        // this is done to make store & address tax requests equal
        return $this->quote->getCustomerGroupId();
    }

    //########################################

    public function getTaxCalculationBasedOn()
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
        $this->mutableConfig->setValue(
            $key,
            $value,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getCode()
        );
    }

    private function getStoreConfig($key)
    {
        return $this->storeConfig->getValue(
            $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getCode()
        );
    }

    //########################################
}
