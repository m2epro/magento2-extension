<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Quote;

/**
 * Builds the quote object, which then can be converted to magento order
 */
class Builder extends \Ess\M2ePro\Model\AbstractModel
{
    const PROCESS_QUOTE_ID = 'PROCESS_QUOTE_ID';

    //########################################

    protected $proxyOrder;

    /** @var  \Magento\Quote\Model\Quote */
    protected $quote;

    protected $currency;
    protected $magentoCurrencyFactory;
    protected $calculation;
    protected $storeConfig;
    protected $productResource;

    /** @var \Ess\M2ePro\Model\Magento\Quote\Manager  */
    protected $quoteManager;

    /** @var \Ess\M2ePro\Model\Magento\Quote\Store\Configurator */
    protected $storeConfigurator;

    /** @var \Magento\Sales\Model\OrderIncrementIdChecker */
    protected $orderIncrementIdChecker;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Order\ProxyObject $proxyOrder,
        \Ess\M2ePro\Model\Currency $currency,
        \Magento\Directory\Model\CurrencyFactory $magentoCurrencyFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Tax\Model\Calculation $calculation,
        \Magento\Framework\App\Config\ReinitableConfigInterface $storeConfig,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Magento\Quote\Manager $quoteManager,
        \Magento\Sales\Model\OrderIncrementIdChecker $orderIncrementIdChecker
    ) {
        $this->proxyOrder              = $proxyOrder;
        $this->currency                = $currency;
        $this->magentoCurrencyFactory  = $magentoCurrencyFactory;
        $this->calculation             = $calculation;
        $this->storeConfig             = $storeConfig;
        $this->productResource         = $productResource;
        $this->quoteManager            = $quoteManager;
        $this->orderIncrementIdChecker = $orderIncrementIdChecker;
        parent::__construct($helperFactory, $modelFactory);
    }

    public function __destruct()
    {
        if ($this->storeConfigurator === null) {
            return;
        }

        $this->storeConfigurator->restoreOriginalStoreConfigForOrder();
    }

    //########################################

    public function build()
    {
        try {
            // do not change invoke order
            // ---------------------------------------
            $this->initializeQuote();
            $this->initializeCustomer();
            $this->initializeAddresses();

            $this->configureStore();
            $this->configureTaxCalculation();

            $this->initializeCurrency();
            $this->initializeShippingMethodData();
            $this->initializeQuoteItems();
            $this->initializePaymentMethodData();

            $this->quote = $this->quoteManager->save($this->quote);

            $this->prepareOrderNumber();
            return $this->quote;
            // ---------------------------------------
        } catch (\Exception $e) {

            // Remove ordered items from customer cart
            $this->quote->setIsActive(false);
            $this->quote->removeAllAddresses();
            $this->quote->removeAllItems();

            $this->quote->save();
            throw $e;
        }
    }

    //########################################

    private function initializeQuote()
    {
        $this->quote = $this->quoteManager->getBlankQuote();

        $this->quote->setCheckoutMethod($this->proxyOrder->getCheckoutMethod());
        $this->quote->setStore($this->proxyOrder->getStore());
        $this->quote->getStore()->setData('current_currency', $this->quote->getStore()->getBaseCurrency());

        /**
         * The quote is empty at this moment, so it is not need to collect totals
         */
        $this->quote->setTotalsCollectedFlag(true);
        $this->quote = $this->quoteManager->save($this->quote);
        $this->quote->setTotalsCollectedFlag(false);

        $this->quote->setIsM2eProQuote(true);
        $this->quote->setNeedProcessChannelTaxes(
            $this->proxyOrder->isTaxModeChannel() ||
            ($this->proxyOrder->isTaxModeMixed() &&
                ($this->proxyOrder->hasTax() || $this->proxyOrder->getWasteRecyclingFee()))
        );

        $this->quoteManager->replaceCheckoutQuote($this->quote);

        /** @var \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper */
        $globalDataHelper = $this->getHelper('Data\GlobalData');

        $globalDataHelper->unsetValue(self::PROCESS_QUOTE_ID);
        $globalDataHelper->setValue(self::PROCESS_QUOTE_ID, $this->quote->getId());
    }

    //########################################

    private function initializeCustomer()
    {
        if ($this->proxyOrder->isCheckoutMethodGuest()) {
            $this->quote
                ->setCustomerId(null)
                ->setCustomerEmail($this->proxyOrder->getBuyerEmail())
                ->setCustomerFirstname($this->proxyOrder->getCustomerFirstName())
                ->setCustomerLastname($this->proxyOrder->getCustomerLastName())
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);

            return;
        }

        $this->quote->assignCustomer($this->proxyOrder->getCustomer());
    }

    //########################################

    private function initializeAddresses()
    {
        $billingAddress = $this->quote->getBillingAddress();
        $billingAddress->addData($this->proxyOrder->getBillingAddressData());

        $billingAddress->setLimitCarrier('m2eproshipping');
        $billingAddress->setShippingMethod('m2eproshipping_m2eproshipping');
        $billingAddress->setCollectShippingRates(true);
        $billingAddress->setShouldIgnoreValidation($this->proxyOrder->shouldIgnoreBillingAddressValidation());

        // ---------------------------------------

        $shippingAddress = $this->quote->getShippingAddress();
        $shippingAddress->setSameAsBilling(0); // maybe just set same as billing?
        $shippingAddress->addData($this->proxyOrder->getAddressData());

        $shippingAddress->setLimitCarrier('m2eproshipping');
        $shippingAddress->setShippingMethod('m2eproshipping_m2eproshipping');
        $shippingAddress->setCollectShippingRates(true);

        // ---------------------------------------
    }

    //########################################

    private function initializeCurrency()
    {
        /** @var $currencyHelper \Ess\M2ePro\Model\Currency */
        $currencyHelper = $this->currency;

        if ($currencyHelper->isConvertible($this->proxyOrder->getCurrency(), $this->quote->getStore())) {
            $currentCurrency = $this->magentoCurrencyFactory->create()->load(
                $this->proxyOrder->getCurrency()
            );
        } else {
            $currentCurrency = $this->quote->getStore()->getBaseCurrency();
        }

        $this->quote->getStore()->setData('current_currency', $currentCurrency);
    }

    //########################################

    /**
     * Configure store (invoked only after address, customer and store initialization and before price calculations)
     */
    private function configureStore()
    {
        $this->storeConfigurator = $this->modelFactory->getObject(
            'Magento_Quote_Store_Configurator',
            ['quote' => $this->quote, 'proxyOrder' => $this->proxyOrder]
        );

        $this->storeConfigurator->prepareStoreConfigForOrder();
    }

    //########################################

    private function configureTaxCalculation()
    {
        // this prevents customer session initialization (which affects cookies)
        // see Mage_Tax_Model_Calculation::getCustomer()
        $this->calculation->setCustomer($this->quote->getCustomer());
    }

    //########################################

    private function initializeQuoteItems()
    {
        foreach ($this->proxyOrder->getItems() as $item) {
            $this->clearQuoteItemsCache();

            /** @var $quoteItemBuilder \Ess\M2ePro\Model\Magento\Quote\Item */
            $quoteItemBuilder = $this->modelFactory->getObject('Magento_Quote_Item', [
                'quote' => $this->quote,
                'proxyItem' => $item
            ]);

            $product = $quoteItemBuilder->getProduct();
            $request = $quoteItemBuilder->getRequest();

            // ---------------------------------------
            $productOriginalPrice = (float)$product->getPrice();

            $price = $item->getBasePrice();
            $product->setPrice($price);
            $product->setSpecialPrice($price);
            // ---------------------------------------

            // see Mage_Sales_Model_Observer::substractQtyFromQuotes
            $this->quote->setItemsCount($this->quote->getItemsCount() + 1);
            $this->quote->setItemsQty((float)$this->quote->getItemsQty() + $request->getQty());

            $result = $this->quote->addProduct($product, $request);
            if (is_string($result)) {
                throw new \Ess\M2ePro\Model\Exception($result);
            }

            $quoteItem = $this->quote->getItemByProduct($product);

            if ($quoteItem !== false) {
                $quoteItem->setStoreId($this->quote->getStoreId());
                $quoteItem->setOriginalCustomPrice($item->getPrice());
                $quoteItem->setOriginalPrice($productOriginalPrice);
                $quoteItem->setBaseOriginalPrice($productOriginalPrice);
                $quoteItem->setNoDiscount(1);
                foreach ($quoteItem->getChildren() as $itemChildren) {
                    $itemChildren->getProduct()->setTaxClassId($quoteItem->getProduct()->getTaxClassId());
                }

                $giftMessageId = $quoteItemBuilder->getGiftMessageId();
                if (!empty($giftMessageId)) {
                    $this->quote->setGiftMessageId($giftMessageId);
                    $quoteItem->setGiftMessageId($giftMessageId);
                }

                $quoteItem->setAdditionalData($quoteItemBuilder->getAdditionalData($quoteItem));

                $quoteItem->setWasteRecyclingFee($item->getWasteRecyclingFee() / $item->getQty());
            }
        }

        $allItems = $this->quote->getAllItems();
        $this->quote->getItemsCollection()->removeAllItems();

        foreach ($allItems as $item) {
            $item->save();
            $this->quote->getItemsCollection()->addItem($item);
        }
    }

    /**
     * Mage_Sales_Model_Quote_Address caches items after each collectTotals call. Some extensions calls collectTotals
     * after adding new item to quote in observers. So we need clear this cache before adding new item to quote.
     */
    private function clearQuoteItemsCache()
    {
        foreach ($this->quote->getAllAddresses() as $address) {
            $address->unsetData('cached_items_all');
            $address->unsetData('cached_items_nominal');
            $address->unsetData('cached_items_nonnominal');
        }
    }

    //########################################

    private function initializeShippingMethodData()
    {
        $this->getHelper('Data\GlobalData')->unsetValue('shipping_data');
        $this->getHelper('Data\GlobalData')->setValue('shipping_data', $this->proxyOrder->getShippingData());
    }

    //########################################

    private function initializePaymentMethodData()
    {
        $quotePayment = $this->quote->getPayment();
        $quotePayment->importData($this->proxyOrder->getPaymentData());
    }

    //########################################

    private function prepareOrderNumber()
    {
        if ($this->proxyOrder->isOrderNumberPrefixSourceChannel()) {
            $orderNumber = $this->proxyOrder->getOrderNumberPrefix() . $this->proxyOrder->getChannelOrderNumber();
            $this->orderIncrementIdChecker->isIncrementIdUsed($orderNumber) && $orderNumber .= '(1)';

            $this->quote->setReservedOrderId($orderNumber);
            return;
        }

        $orderNumber = $this->quote->getReservedOrderId();
        empty($orderNumber) && $orderNumber = $this->quote->getResource()->getReservedOrderId($this->quote);
        $orderNumber = $this->proxyOrder->getOrderNumberPrefix() . $orderNumber;

        if ($this->orderIncrementIdChecker->isIncrementIdUsed($orderNumber)) {
            $orderNumber = $this->quote->getResource()->getReservedOrderId($this->quote);
        }

        $this->quote->setReservedOrderId($orderNumber);
    }

    //########################################
}
