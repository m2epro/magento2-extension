<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order;

class Proxy extends \Ess\M2ePro\Model\Order\Proxy
{
    const USER_ID_ATTRIBUTE_CODE = 'ebay_user_id';

    private $taxCalculation;

    private $payment;

    private $attributeFactory;

    private $customerFactory;

    private $customerRepository;

    //########################################

    public function __construct(
        \Magento\Tax\Model\Calculation $taxCalculation,
        \Ess\M2ePro\Model\Magento\Payment $payment,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Ess\M2ePro\Model\Currency $currency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel $order,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->taxCalculation = $taxCalculation;
        $this->payment = $payment;
        $this->attributeFactory = $attributeFactory;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        parent::__construct($currency, $order, $helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return string
     */
    public function getCheckoutMethod()
    {
        if ($this->order->getEbayAccount()->isMagentoOrdersCustomerNew() ||
            $this->order->getEbayAccount()->isMagentoOrdersCustomerPredefined()) {
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
        return $this->order->getEbayAccount()->isMagentoOrdersNumberSourceChannel();
    }

    /**
     * @return bool
     */
    public function isOrderNumberPrefixSourceMagento()
    {
        return $this->order->getEbayAccount()->isMagentoOrdersNumberSourceMagento();
    }

    public function getChannelOrderNumber()
    {
        return $this->order->getEbayOrderId();
    }

    public function getOrderNumberPrefix()
    {
        if (!$this->order->getEbayAccount()->isMagentoOrdersNumberPrefixEnable()) {
            return '';
        }

        return $this->order->getEbayAccount()->getMagentoOrdersNumberPrefix();
    }

    //########################################

    public function getBuyerEmail()
    {
        $addressData = $this->order->getShippingAddress()->getRawData();
        return $addressData['email'];
    }

    //########################################

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getCustomer()
    {
        if ($this->order->getEbayAccount()->isMagentoOrdersCustomerPredefined()) {
            $customerDataObject = $this->customerRepository->getById(
                $this->order->getEbayAccount()->getMagentoOrdersCustomerId()
            );

            if (is_null($customerDataObject->getId())) {
                throw new \Ess\M2ePro\Model\Exception('Customer with ID specified in eBay Account
                    Settings does not exist.');
            }

            return $customerDataObject;
        }

        if ($this->order->getEbayAccount()->isMagentoOrdersCustomerNew()) {
            $userIdAttribute = $this->attributeFactory->create();

            $userIdAttribute->loadByCode(
                $this->customerFactory->create()->getEntityType()->getEntityTypeId(),
                self::USER_ID_ATTRIBUTE_CODE
            );

            /** @var $customerBuilder \Ess\M2ePro\Model\Magento\Customer */
            $customerBuilder = $this->modelFactory->getObject('Magento\Customer');

            if (!$userIdAttribute->getId()) {
                $customerBuilder->buildAttribute(self::USER_ID_ATTRIBUTE_CODE, 'eBay User ID');
            }

            $customerInfo = $this->getAddressData();

            $customerObject = $this->customerFactory->create();
            $customerObject->setWebsiteId($this->order->getEbayAccount()->getMagentoOrdersCustomerNewWebsiteId());
            $customerObject->loadByEmail($customerInfo['email']);

            if (!is_null($customerObject->getId())) {
                $customerObject->setData(self::USER_ID_ATTRIBUTE_CODE, $this->order->getBuyerUserId());
                $customerObject->save();

                return $customerObject->getDataModel();
            }

            $customerInfo['website_id'] = $this->order->getEbayAccount()->getMagentoOrdersCustomerNewWebsiteId();
            $customerInfo['group_id'] = $this->order->getEbayAccount()->getMagentoOrdersCustomerNewGroupId();

            $customerBuilder->setData($customerInfo);
            $customerBuilder->buildCustomer();

            $customerBuilder->getCustomer()->setData(self::USER_ID_ATTRIBUTE_CODE, $this->order->getBuyerUserId());
            $customerBuilder->getCustomer()->save();

            return $customerBuilder->getCustomer()->getDataModel();
        }

        return NULL;
    }

    //########################################

    /**
     * @return array
     */
    public function getAddressData()
    {
        if (!$this->order->isUseGlobalShippingProgram() &&
            !$this->order->isUseClickAndCollect() &&
            !$this->order->isUseInStorePickup()
        ) {
            return parent::getAddressData();
        }

        $addressModel = $this->order->isUseGlobalShippingProgram() ? $this->order->getGlobalShippingWarehouseAddress()
                                                                   : $this->order->getShippingAddress();

        $rawAddressData = $addressModel->getRawData();

        $addressData = array();

        $recipientNameParts = $this->getNameParts($rawAddressData['recipient_name']);
        $addressData['firstname'] = $recipientNameParts['firstname'];
        $addressData['lastname']  = $recipientNameParts['lastname'];

        $customerNameParts = $this->getNameParts($rawAddressData['buyer_name']);
        $addressData['customer_firstname'] = $customerNameParts['firstname'];
        $addressData['customer_lastname']  = $customerNameParts['lastname'];

        $addressData['email']      = $rawAddressData['email'];
        $addressData['country_id'] = $rawAddressData['country_id'];
        $addressData['region']     = $rawAddressData['region'];
        $addressData['region_id']  = $addressModel->getRegionId();
        $addressData['city']       = $rawAddressData['city'];
        $addressData['postcode']   = $rawAddressData['postcode'];
        $addressData['telephone']  = $rawAddressData['telephone'];
        $addressData['company']    = !empty($rawAddressData['company']) ? $rawAddressData['company'] : '';

        // Adding reference id into street array
        // ---------------------------------------
        $referenceId = '';
        $addressData['street'] = !empty($rawAddressData['street']) ? $rawAddressData['street'] : [];

        if ($this->order->isUseGlobalShippingProgram()) {
            $details = $this->order->getGlobalShippingDetails();
            isset($details['warehouse_address']['reference_id']) &&
            $referenceId = 'Ref #'.$details['warehouse_address']['reference_id'];
        }

        if ($this->order->isUseClickAndCollect()) {
            $details = $this->order->getClickAndCollectDetails();
            isset($details['reference_id']) && $referenceId = 'Ref #'.$details['reference_id'];
        }

        if ($this->order->isUseInStorePickup()) {
            $details = $this->order->getInStorePickupDetails();
            isset($details['reference_id']) && $referenceId = 'Ref #'.$details['reference_id'];
        }

        if (!empty($referenceId)) {

            if (count($addressData['street']) >= 2) {
                $addressData['street'] = array(
                    $referenceId,
                    implode(' ', $addressData['street']),
                );
            } else {
                array_unshift($addressData['street'], $referenceId);
            }
        }
        // ---------------------------------------

        $addressData['save_in_address_book'] = 0;

        return $addressData;
    }

    /**
     * @return array
     */
    public function getBillingAddressData()
    {
        if (!$this->order->isUseGlobalShippingProgram()) {
            return parent::getBillingAddressData();
        }

        return parent::getAddressData();
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
        $paymentMethodTitle = $this->order->getPaymentMethod();
        $paymentMethodTitle == 'None' && $paymentMethodTitle = $this->getHelper('Module\Translation')->__(
            'Not Selected Yet'
        );

        $paymentData = array(
            'method'                => $this->payment->getCode(),
            'component_mode'        => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'payment_method'        => $paymentMethodTitle,
            'channel_order_id'      => $this->order->getEbayOrderId(),
            'channel_final_fee'     => $this->convertPrice($this->order->getFinalFee()),
            'cash_on_delivery_cost' => $this->convertPrice($this->order->getCashOnDeliveryCost()),
            'transactions'          => $this->getPaymentTransactions(),
            'tax_id'                => $this->order->getBuyerTaxId(),
        );

        return $paymentData;
    }

    /**
     * @return array
     */
    public function getPaymentTransactions()
    {
        /** @var \Ess\M2ePro\Model\Ebay\Order\ExternalTransaction[] $externalTransactions */
        $externalTransactions = $this->order->getExternalTransactionsCollection()->getItems();

        $paymentTransactions = array();
        foreach ($externalTransactions as $externalTransaction) {
            $paymentTransactions[] = array(
                'transaction_id'   => $externalTransaction->getTransactionId(),
                'sum'              => $externalTransaction->getSum(),
                'fee'              => $externalTransaction->getFee(),
                'transaction_date' => $externalTransaction->getTransactionDate(),
            );
        }

        return $paymentTransactions;
    }

    //########################################

    /**
     * @return array
     */
    public function getShippingData()
    {
        $shippingData = array(
            'shipping_price'  => $this->getBaseShippingPrice(),
            'carrier_title'   => $this->getHelper('Module\Translation')->__('eBay Shipping'),
            'shipping_method' => $this->order->getShippingService(),
        );

        if ($this->order->isUseGlobalShippingProgram()) {
            $globalShippingDetails = $this->order->getGlobalShippingDetails();
            $globalShippingDetails = $globalShippingDetails['service_details'];

            if (!empty($globalShippingDetails['service_details']['service'])) {
                $shippingData['shipping_method'] = $globalShippingDetails['service_details']['service'];
            }
        }

        if ($this->order->isUseClickAndCollect() || $this->order->isUseInStorePickup()) {
            if ($this->order->isUseClickAndCollect()) {
                $shippingData['shipping_method'] = 'Click And Collect | '.$shippingData['shipping_method'];
                $details = $this->order->getClickAndCollectDetails();
            } else {
                $shippingData['shipping_method'] = 'In Store Pickup | '.$shippingData['shipping_method'];
                $details = $this->order->getInStorePickupDetails();
            }

            if (!empty($details['location_id'])) {
                $shippingData['shipping_method'] .= ' | Store ID: '.$details['location_id'];
            }

            if (!empty($details['reference_id'])) {
                $shippingData['shipping_method'] .= ' | Reference ID: '.$details['reference_id'];
            }

            if (!empty($details['delivery_date'])) {
                $shippingData['shipping_method'] .= ' | Delivery Date: '.$details['delivery_date'];
            }
        }

        return $shippingData;
    }

    /**
     * @return float
     */
    protected function getShippingPrice()
    {
        if ($this->order->isUseGlobalShippingProgram()) {
            $globalShippingDetails = $this->order->getGlobalShippingDetails();
            $price = $globalShippingDetails['service_details']['price'];
        } else {
            $price = $this->order->getShippingPrice();
        }

        if ($this->isTaxModeNone() && !$this->isShippingPriceIncludeTax()) {
            $taxAmount = $this->taxCalculation->calcTaxAmount(
                $price, $this->getShippingPriceTaxRate(), false, false
            );

            $price += $taxAmount;
        }

        return $price;
    }

    //########################################

    /**
     * @return array
     */
    public function getChannelComments()
    {
        $comments = array();

        if ($this->order->isUseGlobalShippingProgram()) {
            $comments[] = '<b>'.
                          $this->getHelper('Module\Translation')->__('Global Shipping Program is used for this Order').
                          '</b><br/>';
        }

        $buyerMessage = $this->order->getBuyerMessage();
        if (!empty($buyerMessage)) {
            $comment = '<b>' . $this->getHelper('Module\Translation')->__('Checkout Message From Buyer') . ': </b>';
            $comment .= $buyerMessage . '<br/>';

            $comments[] = $comment;
        }

        return $comments;
    }

    //########################################

    /**
     * @return bool
     */
    public function hasTax()
    {
        return $this->order->hasTax();
    }

    /**
     * @return bool
     */
    public function isSalesTax()
    {
        return $this->order->isSalesTax();
    }

    /**
     * @return bool
     */
    public function isVatTax()
    {
        return $this->order->isVatTax();
    }

    // ---------------------------------------

    /**
     * @return float|int
     */
    public function getProductPriceTaxRate()
    {
        if (!$this->hasTax()) {
            return 0;
        }

        if ($this->isTaxModeNone() || $this->isTaxModeMagento()) {
            return 0;
        }

        return $this->order->getTaxRate();
    }

    /**
     * @return float|int
     */
    public function getShippingPriceTaxRate()
    {
        if (!$this->hasTax()) {
            return 0;
        }

        if ($this->isTaxModeNone() || $this->isTaxModeMagento()) {
            return 0;
        }

        if (!$this->order->isShippingPriceHasTax()) {
            return 0;
        }

        return $this->getProductPriceTaxRate();
    }

    // ---------------------------------------

    /**
     * @return bool|null
     */
    public function isProductPriceIncludeTax()
    {
        $configValue = $this->getHelper('Module')
            ->getConfig()
            ->getGroupValue('/ebay/order/tax/product_price/', 'is_include_tax');

        if (!is_null($configValue)) {
            return (bool)$configValue;
        }

        if ($this->isTaxModeChannel() || ($this->isTaxModeMixed() && $this->hasTax())) {
            return $this->isVatTax();
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
            ->getGroupValue('/ebay/order/tax/shipping_price/', 'is_include_tax');

        if (!is_null($configValue)) {
            return (bool)$configValue;
        }

        if ($this->isTaxModeChannel() || ($this->isTaxModeMixed() && $this->hasTax())) {
            return $this->isVatTax();
        }

        return null;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isTaxModeNone()
    {
        if ($this->order->isUseGlobalShippingProgram()) {
            return true;
        }

        return $this->order->getEbayAccount()->isMagentoOrdersTaxModeNone();
    }

    /**
     * @return bool
     */
    public function isTaxModeChannel()
    {
        return $this->order->getEbayAccount()->isMagentoOrdersTaxModeChannel();
    }

    /**
     * @return bool
     */
    public function isTaxModeMagento()
    {
        return $this->order->getEbayAccount()->isMagentoOrdersTaxModeMagento();
    }

    //########################################
}