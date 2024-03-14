<?php

namespace Ess\M2ePro\Model\Amazon\Order;

/**
 * @property \Ess\M2ePro\Model\Amazon\Order $order
 */
class ProxyObject extends \Ess\M2ePro\Model\Order\ProxyObject
{
    /** @var \Ess\M2ePro\Model\Amazon\Order\Item\ProxyObject[] */
    protected $removedProxyItems = [];
    /** @var \Ess\M2ePro\Model\Amazon\Order\Tax\PriceTaxRateFactory */
    private $priceTaxRateFactory;
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    protected $helper;
    /** @var \Ess\M2ePro\Model\Amazon\Order\UkTaxService */
    private $ukTaxService;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Order\UkTaxService $ukTaxService,
        \Ess\M2ePro\Model\Amazon\Order\Tax\PriceTaxRateFactory $priceTaxRateFactory,
        \Ess\M2ePro\Model\Currency $currency,
        \Ess\M2ePro\Model\Magento\Payment $payment,
        \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel $order,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Order\UserInfoFactory $userInfoFactory,
        \Ess\M2ePro\Helper\Component\Amazon $helper
    ) {
        parent::__construct(
            $currency,
            $payment,
            $order,
            $customerFactory,
            $customerRepository,
            $helperFactory,
            $modelFactory,
            $userInfoFactory
        );
        $this->helper = $helper;
        $this->priceTaxRateFactory = $priceTaxRateFactory;
        $this->ukTaxService = $ukTaxService;
    }

    /**
     * @return mixed
     */
    public function getChannelOrderNumber()
    {
        return $this->order->getAmazonOrderId();
    }

    /**
     * @return array|mixed|string|null
     */
    public function getMagentoShippingCode()
    {
        $marketplaceId = $this->order->getAmazonAccount()->getMarketplaceId();
        $shippingMethod = $this->order->getShippingCategory();
        $location = $this->order->getShippingMapping();
        $amazonShippingMap = $this->helper->getAmazonShippingMap($shippingMethod, $marketplaceId, $location);
        $magentoCode = $amazonShippingMap->getId()
            ? $amazonShippingMap->getMagentoCode()
            : self::DEFAULT_SHIPPING_CODE;

        return $magentoCode;
    }

    /**
     * @return mixed|string
     */
    public function getCarrierCode()
    {
        return explode('_', $this->getMagentoShippingCode())[0];
    }

    public function getOrderNumberPrefix(): string
    {
        $amazonAccount = $this->order->getAmazonAccount();

        if ($this->order->isFulfilledByAmazon()) {
            return $amazonAccount->getMagentoOrdersNumberAfnPrefix(); //AFN
        }

        if ($this->order->isPrime()) {
            return $amazonAccount->getMagentoOrdersNumberPrimePrefix(); //Prime
        }

        if ($this->order->isBusiness()) {
            return $amazonAccount->getMagentoOrdersNumberB2bPrefix(); //B2B
        }

        return $amazonAccount->getMagentoOrdersNumberRegularPrefix(); //General
    }

    /**
     * @return array
     */
    public function getBillingAddressData()
    {
        if ($this->order->getAmazonAccount()->useMagentoOrdersShippingAddressAsBillingAlways()) {
            return parent::getBillingAddressData();
        }

        if (
            $this->order->getAmazonAccount()->useMagentoOrdersShippingAddressAsBillingIfSameCustomerAndRecipient() &&
            $this->order->getShippingAddress()->hasSameBuyerAndRecipient()
        ) {
            return parent::getBillingAddressData();
        }

        $customerUserInfo = $this->createUserInfoFromRawName($this->order->getBuyerName());

        return [
            'prefix' => $customerUserInfo->getPrefix(),
            'firstname' => $customerUserInfo->getFirstName(),
            'middlename' => $customerUserInfo->getMiddleName(),
            'lastname' => $customerUserInfo->getLastName(),
            'suffix' => $customerUserInfo->getSuffix(),
            'country_id' => '',
            'region' => '',
            'region_id' => '',
            'city' => 'Amazon does not supply the complete billing Buyer information.',
            'postcode' => '',
            'street' => '',
            'company' => '',
        ];
    }

    /**
     * @return bool
     */
    public function shouldIgnoreBillingAddressValidation()
    {
        if ($this->order->getAmazonAccount()->useMagentoOrdersShippingAddressAsBillingAlways()) {
            return false;
        }

        if (
            $this->order->getAmazonAccount()->useMagentoOrdersShippingAddressAsBillingIfSameCustomerAndRecipient() &&
            $this->order->getShippingAddress()->hasSameBuyerAndRecipient()
        ) {
            return false;
        }

        return true;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAddressData()
    {
        parent::getAddressData();

        $amazonAccount = $this->order->getAmazonAccount();
        $data = $amazonAccount->getData('magento_orders_settings');
        $data = !empty($data) ? \Ess\M2ePro\Helper\Json::decode($data) : [];

        if (!empty($data['tax']['import_tax_id_in_magento_order'])) {
            $this->addressData['vat_id'] = $this->order->getTaxRegistrationId();
        }

        return $this->addressData;
    }

    public function getCustomerFirstName()
    {
        $addressData = $this->getAddressData();

        return !empty($addressData['customer_firstname'])
            ? $addressData['customer_firstname']
            : $addressData['firstname'];
    }

    public function getCustomerMiddleName()
    {
        $addressData = $this->getAddressData();

        return !empty($addressData['customer_middlename'])
            ? $addressData['customer_middlename']
            : $addressData['middlename'];
    }

    public function getCustomerLastName()
    {
        $addressData = $this->getAddressData();

        return !empty($addressData['customer_lastname'])
            ? $addressData['customer_lastname']
            : $addressData['lastname'];
    }

    /**
     * @return array
     */
    public function getPaymentData()
    {
        $paymentData = [
            'method' => $this->payment->getCode(),
            'component_mode' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'payment_method' => '',
            'channel_order_id' => $this->order->getAmazonOrderId(),
            'channel_final_fee' => 0,
            'cash_on_delivery_cost' => 0,
            'transactions' => [],
        ];

        return $paymentData;
    }

    public function getShippingData(): array
    {
        $amazonAccount = $this->order->getAmazonAccount();

        $additionalData = '';

        if ($amazonAccount->isImportLabelsToMagentoOrder()) {
            if ($this->order->isPrime()) {
                $additionalData .= 'Is Prime | ';
            }

            if ($this->order->isBusiness()) {
                $additionalData .= 'Is Business | ';
            }

            if ($this->order->isSoldByAmazon()) {
                $additionalData .= 'Invoice by Amazon | ';
            }
        }

        if ($this->order->isMerchantFulfillmentApplied()) {
            $merchantFulfillmentInfo = $this->order->getMerchantFulfillmentData();

            $additionalData .= 'Amazon\'s Shipping Services | ';

            if (!empty($merchantFulfillmentInfo['shipping_service']['carrier_name'])) {
                $carrier = $merchantFulfillmentInfo['shipping_service']['carrier_name'];
                $additionalData .= 'Carrier: ' . $carrier . ' | ';
            }

            if (!empty($merchantFulfillmentInfo['shipping_service']['name'])) {
                $service = $merchantFulfillmentInfo['shipping_service']['name'];
                $additionalData .= 'Service: ' . $service . ' | ';
            }

            if (!empty($merchantFulfillmentInfo['shipping_service']['date']['estimated_delivery']['latest'])) {
                $deliveryDate = $merchantFulfillmentInfo['shipping_service']['date']['estimated_delivery']['latest'];
                $additionalData .= 'Delivery Date: ' . $deliveryDate . ' | ';
            }
        }

        $shippingDateTo = $this->order->getShippingDateTo();
        $isImportShipByDate = $amazonAccount->isImportShipByDateToMagentoOrder();

        if (!empty($shippingDateTo) && $isImportShipByDate) {
            $shippingDate = $this->getHelper('Data')->gmtDateToTimezone(
                $shippingDateTo,
                false,
                'M d, Y, H:i:s'
            );
            $additionalData .= "Ship By Date: {$shippingDate} | ";
        }

        if ($iossNumber = $this->order->getIossNumber()) {
            $additionalData .= 'IOSS Number: ' . $iossNumber . ' | ';
        }

        if (!empty($additionalData)) {
            $additionalData = ' | ' . $additionalData;
        }

        return [
            'carrier_title' => __('Amazon Shipping'),
            'shipping_method' => $this->order->getShippingService() . $additionalData,
            'shipping_price' => $this->getBaseShippingPrice(),
        ];
    }

    /**
     * @return float
     */
    protected function getShippingPrice()
    {
        $price = $this->order->getShippingPrice() - $this->order->getShippingDiscountAmount();

        if ($this->isTaxModeNone() && $this->getShippingPriceTaxRate() > 0) {
            $price += $this->order->getShippingPriceTaxAmount();
        }

        return $price;
    }

    /**
     * @return string[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getChannelComments()
    {
        return array_merge(
            $this->getDiscountComments(),
            $this->getGiftWrappedComments(),
            $this->getRemovedOrderItemsComments(),
            $this->getAFNWarehouseComments(),
            $this->getCustomizationComments()
        );
    }

    /**
     * @return string[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getDiscountComments()
    {
        $translation = $this->getHelper('Module_Translation');
        $comments = [];

        if ($this->order->getPromotionDiscountAmount() > 0) {
            $discount = $this->currency->formatPrice(
                $this->getCurrency(),
                $this->order->getPromotionDiscountAmount()
            );

            $comments[] = $translation->__(
                '%value% promotion discount amount was subtracted from the total amount.',
                $discount
            );
        }

        if ($this->order->getShippingDiscountAmount() > 0) {
            $discount = $this->currency->formatPrice(
                $this->getCurrency(),
                $this->order->getShippingDiscountAmount()
            );

            $comments[] = $translation->__(
                '%value% discount amount was subtracted from the shipping Price.',
                $discount
            );
        }

        return $comments;
    }

    /**
     * @return string[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getGiftWrappedComments()
    {
        $itemsGiftPrices = [];
        foreach ($this->order->getParentObject()->getItemsCollection() as $item) {
            /** @var \Ess\M2ePro\Model\Order\Item $item */

            $giftPrice = $item->getChildObject()->getGiftPrice();
            if (empty($giftPrice)) {
                continue;
            }

            if ($item->getMagentoProduct()) {
                $itemsGiftPrices[] = [
                    'name' => $item->getMagentoProduct()->getName(),
                    'type' => $item->getChildObject()->getGiftType(),
                    'price' => $giftPrice,
                ];
            }
        }

        if (empty($itemsGiftPrices)) {
            return [];
        }

        $comment = '<u>' .
            $this->getHelper('Module_Translation')->__('The following Items are purchased with gift wraps') .
            ':</u><br/>';

        foreach ($itemsGiftPrices as $productInfo) {
            $formattedCurrency = $this->currency->formatPrice(
                $this->getCurrency(),
                $productInfo['price']
            );

            $comment .= "<b>{$productInfo['name']}</b> > {$productInfo['type']} ({$formattedCurrency})<br/>";
        }

        return [$comment];
    }

    /**
     * @return string[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRemovedOrderItemsComments()
    {
        if (empty($this->removedProxyItems)) {
            return [];
        }

        $comment = '<u>' .
            $this->getHelper('Module_Translation')->__(
                'The following SKUs have zero price and can not be included in Magento order line items'
            ) .
            ':</u><br/>';

        foreach ($this->removedProxyItems as $item) {
            if ($item->getMagentoProduct()) {
                $comment .= "<b>{$item->getMagentoProduct()->getSku()}</b>: {$item->getQty()} QTY<br/>";
            }
        }

        return [$comment];
    }

    /**
     * @return string[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAFNWarehouseComments()
    {
        if (!$this->order->isFulfilledByAmazon()) {
            return [];
        }

        $comment = '';
        $helper = $this->getHelper('Data');
        $translation = $this->getHelper('Module_Translation');

        foreach ($this->order->getParentObject()->getItemsCollection() as $item) {
            /** @var \Ess\M2ePro\Model\Order\Item $item */

            $centerId = $item->getChildObject()->getFulfillmentCenterId();
            if (empty($centerId)) {
                return [];
            }

            if ($item->getMagentoProduct()) {
                $sku = $item->getMagentoProduct()->getSku();
                $comment .= "<b>{$translation->__('SKU')}:</b> {$helper->escapeHtml($sku)}&nbsp;&nbsp;&nbsp;";
            }

            if ($generalId = $item->getChildObject()->getGeneralId()) {
                $general = $item->getChildObject()->getIsIsbnGeneralId() ? 'ISBN' : 'ASIN';
                $comment .= "<b>{$translation->__($general)}:</b> {$helper->escapeHtml($generalId)}&nbsp;&nbsp;&nbsp;";
            }

            $comment .= "<b>{$translation->__('AFN Warehouse')}:</b> {$helper->escapeHtml($centerId)}<br/><br/>";
        }

        return empty($comment) ? [] : [$comment];
    }

    /**
     * @return string[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getCustomizationComments(): array
    {
        $comments = '';
        foreach ($this->order->getParentObject()->getItemsCollection() as $orderItem) {
            /** @var \Ess\M2ePro\Model\Amazon\Order\Item $amazonOrderItem */
            $amazonOrderItem = $orderItem->getChildObject();
            if ($amazonOrderItem->hasCustomizedInfo()) {
                $comments .= __(
                    "Customization for SKU %sku: <a href='%customized_link'>Link</a><br>",
                    [
                        'sku' => $amazonOrderItem->getSku(),
                        'customized_link' => $amazonOrderItem->getCustomizedInfo()
                    ]
                );
            }
        }

        return [$comments];
    }

    /**
     * @return bool
     */
    public function hasTax()
    {
        return $this->order->getProductPriceTaxRate() > 0;
    }

    /**
     * @return bool
     */
    public function isSalesTax()
    {
        return $this->hasTax();
    }

    /**
     * @return bool
     */
    public function isVatTax()
    {
        return false;
    }

    /**
     * @return float|int
     */
    public function getProductPriceTaxRate()
    {
        return $this->getProductPriceTaxRateObject()->getValue();
    }

    /**
     * @return \Ess\M2ePro\Model\Order\Tax\PriceTaxRateInterface
     */
    public function getProductPriceTaxRateObject(): ?\Ess\M2ePro\Model\Order\Tax\PriceTaxRateInterface
    {
        return $this->priceTaxRateFactory->createProductPriceTaxRateByOrder($this->order);
    }

    /**
     * @return float|int
     */
    public function getShippingPriceTaxRate()
    {
        return $this->getShippingPriceTaxRateObject()->getValue();
    }

    public function getShippingPriceTaxRateObject(): ?\Ess\M2ePro\Model\Order\Tax\PriceTaxRateInterface
    {
        return $this->priceTaxRateFactory->createShippingPriceTaxRateByOrder($this->order);
    }

    // ---------------------------------------

    public function isTaxModeNone(): bool
    {
        $isNeedToSkipTax = $this->order->getAmazonAccount()->isAmazonCollectsTaxForUKShipmentWithCertainPrice();
        $isSkipTaxForUkShipmentCountryCode = $this->ukTaxService->isSkipTaxForUkShipmentCountryCode(
            $this->order->getShippingAddress()->getData('country_code')
        );

        if ($isNeedToSkipTax && $isSkipTaxForUkShipmentCountryCode) {
            try {
                if (
                    $this->ukTaxService->isSumOfItemPriceLessThan135GBP(
                        $this->calculateItemsPrice()
                    )
                ) {
                    return true;
                }
            } catch (\Throwable $exception) {
            }
        }

        return parent::isTaxModeNone();
    }

    private function calculateItemsPrice(): float
    {
        $result = 0.0;

        foreach ($this->order->getParentObject()->getItemsCollection() as $orderItem) {
            /** @var \Ess\M2ePro\Model\Amazon\Order\Item $amazonOrderItem */
            $amazonOrderItem = $orderItem->getChildObject();
            $result += $this->ukTaxService->convertPricetoGBP(
                $amazonOrderItem->getPrice(),
                trim($amazonOrderItem->getCurrency())
            );
        }

        return $result;
    }
}
