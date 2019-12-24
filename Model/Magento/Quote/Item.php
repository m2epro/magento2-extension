<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Quote;

/**
 * Class \Ess\M2ePro\Model\Magento\Quote\Item
 */
class Item extends \Ess\M2ePro\Model\AbstractModel
{
    protected $taxHelper;

    protected $productFactory;

    protected $calculation;

    protected $messageFactory;

    /** @var \Magento\Quote\Model\Quote */
    protected $quote;

    /** @var \Ess\M2ePro\Model\Order\Item\ProxyObject */
    protected $proxyItem;

    /** @var \Magento\Catalog\Model\Product */
    protected $product;

    /** @var \Magento\GiftMessage\Model\Message */
    protected $giftMessage;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Tax\Helper $taxHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Tax\Model\Calculation $calculation,
        \Magento\GiftMessage\Model\MessageFactory $messageFactory,
        \Magento\Quote\Model\Quote $quote,
        \Ess\M2ePro\Model\Order\Item\ProxyObject $proxyItem,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->taxHelper = $taxHelper;
        $this->productFactory = $productFactory;
        $this->calculation = $calculation;
        $this->messageFactory = $messageFactory;
        $this->quote = $quote;
        $this->proxyItem = $proxyItem;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return \Magento\Catalog\Model\Product|null
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getProduct()
    {
        if ($this->product !== null) {
            return $this->product;
        }

        if ($this->proxyItem->getMagentoProduct()->isGroupedType()) {
            $this->product = $this->getAssociatedGroupedProduct();

            if ($this->product === null) {
                throw new \Ess\M2ePro\Model\Exception('There are no associated Products found for Grouped Product.');
            }
        } else {
            $this->product = $this->proxyItem->getProduct();

            if ($this->proxyItem->getMagentoProduct()->isBundleType()) {
                $this->product->setPriceType(\Magento\Catalog\Model\Product\Type\AbstractType::CALCULATE_PARENT);
            }
        }

        // tax class id should be set before price calculation
        $this->product->setTaxClassId($this->getProductTaxClassId());

        return $this->product;
    }

    // ---------------------------------------

    private function getAssociatedGroupedProduct()
    {
        $associatedProducts = $this->proxyItem->getAssociatedProducts();
        $associatedProductId = reset($associatedProducts);

        $product = $this->productFactory->create()
            ->setStoreId($this->quote->getStoreId())
            ->load($associatedProductId);

        return $product->getId() ? $product : null;
    }

    //########################################

    private function getProductTaxClassId()
    {
        $proxyOrder = $this->proxyItem->getProxyOrder();
        $itemTaxRate = $this->proxyItem->getTaxRate();
        $isOrderHasTax = $this->proxyItem->getProxyOrder()->hasTax();
        $hasRatesForCountry = $this->taxHelper->hasRatesForCountry($this->quote->getShippingAddress()->getCountryId());
        $calculationBasedOnOrigin = $this->taxHelper->isCalculationBasedOnOrigin($this->quote->getStore());

        if ($proxyOrder->isTaxModeNone()
            || ($proxyOrder->isTaxModeChannel() && $itemTaxRate <= 0)
            || ($proxyOrder->isTaxModeMagento() && !$hasRatesForCountry && !$calculationBasedOnOrigin)
            || ($proxyOrder->isTaxModeMixed() && $itemTaxRate <= 0 && $isOrderHasTax)
        ) {
            return \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE;
        }

        if ($proxyOrder->isTaxModeMagento()
            || $itemTaxRate <= 0
            || $itemTaxRate == $this->getProductTaxRate()
        ) {
            return $this->getProduct()->getTaxClassId();
        }

        // Create tax rule according to channel tax rate
        // ---------------------------------------
        /** @var $taxRuleBuilder \Ess\M2ePro\Model\Magento\Tax\Rule\Builder */
        $taxRuleBuilder = $this->modelFactory->getObject('Magento_Tax_Rule_Builder');
        $taxRuleBuilder->buildProductTaxRule(
            $itemTaxRate,
            $this->quote->getShippingAddress()->getCountryId(),
            $this->quote->getCustomerTaxClassId()
        );

        $taxRule = $taxRuleBuilder->getRule();
        $productTaxClasses = $taxRule->getProductTaxClasses();
        // ---------------------------------------

        return array_shift($productTaxClasses);
    }

    private function getProductTaxRate()
    {
        /** @var $taxCalculator \Magento\Tax\Model\Calculation */
        $taxCalculator = $this->calculation;

        $request = $taxCalculator->getRateRequest(
            $this->quote->getShippingAddress(),
            $this->quote->getBillingAddress(),
            $this->quote->getCustomerTaxClassId(),
            $this->quote->getStore()
        );
        $request->setProductClassId($this->getProduct()->getTaxClassId());

        return $taxCalculator->getRate($request);
    }

    //########################################

    public function getRequest()
    {
        $request = new \Magento\Framework\DataObject();
        $request->setQty($this->proxyItem->getQty());

        // grouped and downloadable products doesn't have options
        if ($this->proxyItem->getMagentoProduct()->isGroupedType() ||
            $this->proxyItem->getMagentoProduct()->isDownloadableType()) {
            return $request;
        }

        /** @var $magentoProduct \Ess\M2ePro\Model\Magento\Product */
        $magentoProduct = $this->modelFactory->getObject('Magento\Product')->setProduct($this->getProduct());
        $options = $this->proxyItem->getOptions();

        if (empty($options)) {
            return $request;
        }

        if ($magentoProduct->isSimpleType()) {
            $request->setOptions($options);
        } elseif ($magentoProduct->isBundleType()) {
            $request->setBundleOption($options);
        } elseif ($magentoProduct->isConfigurableType()) {
            $request->setSuperAttribute($options);
        } elseif ($magentoProduct->isDownloadableType()) {
            $request->setLinks($options);
        }

        return $request;
    }

    //########################################

    public function getGiftMessageId()
    {
        $giftMessage = $this->getGiftMessage();

        return $giftMessage ? $giftMessage->getId() : null;
    }

    public function getGiftMessage()
    {
        if ($this->giftMessage !== null) {
            return $this->giftMessage;
        }

        $giftMessageData = $this->proxyItem->getGiftMessage();

        if (!is_array($giftMessageData)) {
            return null;
        }

        $giftMessageData['customer_id'] = (int)$this->quote->getCustomerId();
        /** @var $giftMessage \Magento\GiftMessage\Model\Message */
        $giftMessage = $this->messageFactory->create()->addData($giftMessageData);

        if ($giftMessage->isMessageEmpty()) {
            return null;
        }

        $this->giftMessage = $giftMessage->save();

        return $this->giftMessage;
    }

    //########################################

    public function getAdditionalData(\Magento\Quote\Model\Quote\Item $quoteItem)
    {
        $additionalData      = $this->proxyItem->getAdditionalData();
        $existAdditionalData = is_string($quoteItem->getAdditionalData())
                               ? $this->getHelper('Data')->unserialize($quoteItem->getAdditionalData())
                               : [];

        return $this->getHelper('Data')->serialize(array_merge($existAdditionalData, $additionalData));
    }

    //########################################
}
