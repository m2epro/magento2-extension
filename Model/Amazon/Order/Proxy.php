<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order;

class Proxy extends \Ess\M2ePro\Model\Order\Proxy
{
    protected $payment;

    protected $customerFactory;

    private $customerRepository;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Magento\Payment $payment,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Ess\M2ePro\Model\Currency $currency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel $order,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->payment = $payment;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        parent::__construct($currency, $order, $helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order\Item\Proxy[] $items
     * @return \Ess\M2ePro\Model\Order\Item\Proxy[]
     * @throws \Exception
     */
    protected function mergeItems(array $items)
    {
        foreach ($items as $key => $item) {
            if ($item->getPrice() <= 0) {
                unset($items[$key]);
            }
        }

        if (count($items) == 0) {
            throw new \Ess\M2ePro\Model\Exception('Every Item in Order has zero Price.');
        }

        return parent::mergeItems($items);
    }

    //########################################

    /**
     * @return string
     */
    public function getCheckoutMethod()
    {
        if ($this->order->getAmazonAccount()->isMagentoOrdersCustomerPredefined() ||
            $this->order->getAmazonAccount()->isMagentoOrdersCustomerNew()) {
            return self::CHECKOUT_REGISTER;
        }

        return self::CHECKOUT_GUEST;
    }

    //########################################

    /**
     * @return bool
     */
    public function isOrderNumberPrefixSourceChannel()
    {
        return $this->order->getAmazonAccount()->isMagentoOrdersNumberSourceChannel();
    }

    /**
     * @return bool
     */
    public function isOrderNumberPrefixSourceMagento()
    {
        return $this->order->getAmazonAccount()->isMagentoOrdersNumberSourceMagento();
    }

    /**
     * @return mixed
     */
    public function getChannelOrderNumber()
    {
        return $this->order->getAmazonOrderId();
    }

    /**
     * @return null|string
     */
    public function getOrderNumberPrefix()
    {
        if (!$this->order->getAmazonAccount()->isMagentoOrdersNumberPrefixEnable()) {
            return '';
        }

        return $this->order->getAmazonAccount()->getMagentoOrdersNumberPrefix();
    }

    //########################################

    /**
     * @return mixed
     */
    public function getBuyerEmail()
    {
        return $this->order->getBuyerEmail();
    }

    //########################################

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getCustomer()
    {
        if ($this->order->getAmazonAccount()->isMagentoOrdersCustomerPredefined()) {
            $customerDataObject = $this->customerRepository->getById(
                $this->order->getAmazonAccount()->getMagentoOrdersCustomerId()
            );

            if (is_null($customerDataObject->getId())) {
                throw new \Ess\M2ePro\Model\Exception('Customer with ID specified in Amazon Account
                    Settings does not exist.');
            }

            return $customerDataObject;
        }

        if ($this->order->getAmazonAccount()->isMagentoOrdersCustomerNew()) {
            $customerInfo = $this->getAddressData();

            $customerObject = $this->customerFactory->create();
            $customerObject->setWebsiteId($this->order->getAmazonAccount()->getMagentoOrdersCustomerNewWebsiteId());
            $customerObject->loadByEmail($customerInfo['email']);

            if (!is_null($customerObject->getId())) {
                return $customerObject->getDataModel();
            }

            $customerInfo['website_id'] = $this->order->getAmazonAccount()->getMagentoOrdersCustomerNewWebsiteId();
            $customerInfo['group_id'] = $this->order->getAmazonAccount()->getMagentoOrdersCustomerNewGroupId();

            /** @var $customerBuilder \Ess\M2ePro\Model\Magento\Customer */
            $customerBuilder = $this->modelFactory->getObject('Magento\Customer');
            $customerBuilder->setData($customerInfo);
            $customerBuilder->buildCustomer();
            $customerBuilder->getCustomer()->save();

            return $customerBuilder->getCustomer()->getDataModel();
        }

        return NULL;
    }

    //########################################

    /**
     * @return array
     */
    public function getBillingAddressData()
    {
        if ($this->order->getAmazonAccount()->isMagentoOrdersBillingAddressSameAsShipping()) {
            return parent::getBillingAddressData();
        }

        if ($this->order->getShippingAddress()->hasSameBuyerAndRecipient()) {
            return parent::getBillingAddressData();
        }

        $customerNameParts = $this->getNameParts($this->order->getBuyerName());

        return array(
            'firstname'  => $customerNameParts['firstname'],
            'lastname'   => $customerNameParts['lastname'],
            'country_id' => '',
            'region'     => '',
            'region_id'  => '',
            'city'       => 'Amazon does not supply the complete billing Buyer information.',
            'postcode'   => '',
            'street'     => '',
            'company'    => ''
        );
    }

    /**
     * @return bool
     */
    public function shouldIgnoreBillingAddressValidation()
    {
        if ($this->order->getAmazonAccount()->isMagentoOrdersBillingAddressSameAsShipping()) {
            return false;
        }

        if ($this->order->getShippingAddress()->hasSameBuyerAndRecipient()) {
            return false;
        }

        return true;
    }

    //########################################

    public function getCurrency()
    {
        return $this->order->getCurrency();
    }

    //########################################

    /**
     * @return array
     */
    public function getPaymentData()
    {
        $paymentData = array(
            'method'                => $this->payment->getCode(),
            'component_mode'        =>\Ess\M2ePro\Helper\Component\Amazon::NICK,
            'payment_method'        => '',
            'channel_order_id'      => $this->order->getAmazonOrderId(),
            'channel_final_fee'     => 0,
            'cash_on_delivery_cost' => 0,
            'transactions'          => array()
        );

        return $paymentData;
    }

    //########################################

    /**
     * @return array
     */
    public function getShippingData()
    {
        $shippingData = array(
            'shipping_method' => $this->order->getShippingService(),
            'shipping_price'  => $this->getBaseShippingPrice(),
            'carrier_title'   => $this->getHelper('Module\Translation')->__('Amazon Shipping')
        );

        if ($this->order->isPrime()) {
            $shippingData['shipping_method'] .= ' | Is Prime';
        }

        if ($this->order->isBusiness()) {
            $shippingData['shipping_method'] .= ' | Is Business';
        }

        if ($this->order->isMerchantFulfillmentApplied()) {
            $merchantFulfillmentInfo = $this->order->getMerchantFulfillmentData();

            $shippingData['shipping_method'] .= ' | Amazon\'s Shipping Services';

            if (!empty($merchantFulfillmentInfo['shipping_service']['carrier_name'])) {
                $carrier = $merchantFulfillmentInfo['shipping_service']['carrier_name'];
                $shippingData['shipping_method'] .= ' | Carrier: '.$carrier;
            }

            if (!empty($merchantFulfillmentInfo['shipping_service']['name'])) {
                $service = $merchantFulfillmentInfo['shipping_service']['name'];
                $shippingData['shipping_method'] .= ' | Service: '.$service;
            }

            if (!empty($merchantFulfillmentInfo['shipping_service']['date']['estimated_delivery']['latest'])) {
                $deliveryDate = $merchantFulfillmentInfo['shipping_service']['date']['estimated_delivery']['latest'];
                $shippingData['shipping_method'] .= ' | Delivery Date: '.$deliveryDate;
            }
        }

        return $shippingData;
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
     * @return array
     */
    public function getChannelComments()
    {
        $translator = $this->getHelper('Module\Translation');

        $comments = array();

        if ($this->order->getPromotionDiscountAmount() > 0) {
            $discount = $this->currency->formatPrice($this->getCurrency(), $this->order->getPromotionDiscountAmount());

            $comment = $translator->__(
                '%value% promotion discount amount was subtracted from the total amount.',
                $discount
            );
            $comment .= '<br/>';

            $comments[] = $comment;
        }

        if ($this->order->getShippingDiscountAmount() > 0) {
            $discount = $this->currency->formatPrice($this->getCurrency(), $this->order->getShippingDiscountAmount());

            $comment = $translator->__(
                '%value% discount amount was subtracted from the shipping Price.',
                $discount
            );
            $comment .= '<br/>';

            $comments[] = $comment;
        }

        // Gift Wrapped Items
        // ---------------------------------------
        $itemsGiftPrices = array();

        /** @var \Ess\M2ePro\Model\Order\Item[] $items */
        $items = $this->order->getParentObject()->getItemsCollection();
        foreach ($items as $item) {
            $giftPrice = $item->getChildObject()->getGiftPrice();
            if (empty($giftPrice)) {
                continue;
            }

            $itemsGiftPrices[] = array(
                'name'    => $item->getMagentoProduct()->getName(),
                'type'    => $item->getChildObject()->getGiftType(),
                'price'   => $giftPrice,
                'message' => $item->getChildObject()->getGiftMessage(),
            );
        }

        if (!empty($itemsGiftPrices)) {

            $comment = '<u>'.$translator->__('The following Items are purchased with gift wraps').':</u><br/>';

            foreach ($itemsGiftPrices as $productInfo) {
                $formattedCurrency = $this->currency->formatPrice(
                    $this->getCurrency(), $productInfo['price']
                );

                $comment .= '<b>'.$productInfo['name'].'</b> > '.$productInfo['type'].' ('.$formattedCurrency.')<br />';

                if (!empty($productInfo['message'])) {
                    $comment .= '<i>'.$translator->__('Message').':</i> '.$productInfo['message'].'<br />';
                }
            }

            $comments[] = $comment;
        }
        // ---------------------------------------

        return $comments;
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

    // ---------------------------------------

    /**
     * @return bool|null
     */
    public function isProductPriceIncludeTax()
    {
        $configValue = $this->getHelper('Module')
            ->getConfig()
            ->getGroupValue('/amazon/order/tax/product_price/', 'is_include_tax');

        if (!is_null($configValue)) {
            return (bool)$configValue;
        }

        if ($this->isTaxModeChannel() || ($this->isTaxModeMixed() && $this->hasTax())) {
            return false;
        }

        return null;
    }

    /**
     * @return bool|null
     */
    public function isShippingPriceIncludeTax()
    {
        $configValue = $this->getHelper('Module')
            ->getConfig()
            ->getGroupValue('/amazon/order/tax/shipping_price/', 'is_include_tax');

        if (!is_null($configValue)) {
            return (bool)$configValue;
        }

        if ($this->isTaxModeChannel() || ($this->isTaxModeMixed() && $this->hasTax())) {
            return false;
        }

        return null;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isTaxModeNone()
    {
        return $this->order->getAmazonAccount()->isMagentoOrdersTaxModeNone();
    }

    /**
     * @return bool
     */
    public function isTaxModeMagento()
    {
        return $this->order->getAmazonAccount()->isMagentoOrdersTaxModeMagento();
    }

    /**
     * @return bool
     */
    public function isTaxModeChannel()
    {
        return $this->order->getAmazonAccount()->isMagentoOrdersTaxModeChannel();
    }

    //########################################
}