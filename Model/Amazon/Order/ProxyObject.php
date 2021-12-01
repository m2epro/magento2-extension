<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order;

/**
 * Class \Ess\M2ePro\Model\Amazon\Order\ProxyObject
 */
class ProxyObject extends \Ess\M2ePro\Model\Order\ProxyObject
{
    /** @var \Ess\M2ePro\Model\Amazon\Order\Item\ProxyObject[] */
    protected $removedProxyItems = [];

    //########################################

    /**
     * @return mixed
     */
    public function getChannelOrderNumber()
    {
        return $this->order->getAmazonOrderId();
    }

    /**
     * @return string
     */
    public function getOrderNumberPrefix()
    {
        $amazonAccount = $this->order->getAmazonAccount();

        $prefix = $amazonAccount->getMagentoOrdersNumberRegularPrefix();

        if ($amazonAccount->getMagentoOrdersNumberAfnPrefix() && $this->order->isFulfilledByAmazon()) {
            $prefix .= $amazonAccount->getMagentoOrdersNumberAfnPrefix();
        }

        if ($amazonAccount->getMagentoOrdersNumberPrimePrefix() && $this->order->isPrime()) {
            $prefix .= $amazonAccount->getMagentoOrdersNumberPrimePrefix();
        }

        if ($amazonAccount->getMagentoOrdersNumberB2bPrefix() && $this->order->isBusiness()) {
            $prefix .= $amazonAccount->getMagentoOrdersNumberB2bPrefix();
        }

        return $prefix;
    }

    //########################################

    /**
     * @return array
     */
    public function getBillingAddressData()
    {
        if ($this->order->getAmazonAccount()->useMagentoOrdersShippingAddressAsBillingAlways()) {
            return parent::getBillingAddressData();
        }

        if ($this->order->getAmazonAccount()->useMagentoOrdersShippingAddressAsBillingIfSameCustomerAndRecipient() &&
            $this->order->getShippingAddress()->hasSameBuyerAndRecipient()
        ) {
            return parent::getBillingAddressData();
        }

        $customerNameParts = $this->getNameParts($this->order->getBuyerName());

        return [
            'prefix'     => $customerNameParts['prefix'],
            'firstname'  => $customerNameParts['firstname'],
            'middlename' => $customerNameParts['middlename'],
            'lastname'   => $customerNameParts['lastname'],
            'suffix'     => $customerNameParts['suffix'],
            'country_id' => '',
            'region'     => '',
            'region_id'  => '',
            'city'       => 'Amazon does not supply the complete billing Buyer information.',
            'postcode'   => '',
            'street'     => '',
            'company'    => ''
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

        if ($this->order->getAmazonAccount()->useMagentoOrdersShippingAddressAsBillingIfSameCustomerAndRecipient() &&
            $this->order->getShippingAddress()->hasSameBuyerAndRecipient()
        ) {
            return false;
        }

        return true;
    }

    //########################################

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
            'transactions' => []
        ];

        return $paymentData;
    }

    //########################################

    /**
     * @return array
     */
    public function getShippingData()
    {
        $additionalData = '';

        if ($this->order->isPrime()) {
            $additionalData .= 'Is Prime | ';
        }

        if ($this->order->isBusiness()) {
            $additionalData .= 'Is Business | ';
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
        if (!empty($shippingDateTo)) {
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
            'carrier_title'   => $this->getHelper('Module\Translation')->__('Amazon Shipping'),
            'shipping_method' => $this->order->getShippingService() . $additionalData,
            'shipping_price'  => $this->getBaseShippingPrice()
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

    //########################################

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
            $this->getAFNWarehouseComments()
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

            $comments[] =  $translation->__(
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
                    'name'  => $item->getMagentoProduct()->getName(),
                    'type'  => $item->getChildObject()->getGiftType(),
                    'price' => $giftPrice
                ];
            }
        }

        if (empty($itemsGiftPrices)) {
            return [];
        }

        $comment = '<u>'.
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

        $comment = '<u>'.
            $this->getHelper('Module_Translation')->__(
                'The following SKUs have zero price and can not be included in Magento order line items'
            ).
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

    //########################################

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

    // ---------------------------------------

    /**
     * @return float|int
     */
    public function getProductPriceTaxRate()
    {
        return $this->order->getProductPriceTaxRate();
    }

    /**
     * @return float|int
     */
    public function getShippingPriceTaxRate()
    {
        return $this->order->getShippingPriceTaxRate();
    }

    //########################################
}
