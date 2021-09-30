<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Order;

/**
 * Class \Ess\M2ePro\Model\Ebay\Order\ProxyObject
 */
class ProxyObject extends \Ess\M2ePro\Model\Order\ProxyObject
{
    const USER_ID_ATTRIBUTE_CODE = 'ebay_user_id';

    /** @var \Magento\Tax\Model\Calculation */
    private $taxCalculation;

    /** @var \Magento\Eav\Model\Entity\AttributeFactory */
    private $attributeFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Currency $currency,
        \Ess\M2ePro\Model\Magento\Payment $payment,
        \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel $order,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Tax\Model\Calculation $taxCalculation,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory
    ) {
        $this->taxCalculation = $taxCalculation;
        $this->attributeFactory = $attributeFactory;

        parent::__construct(
            $currency,
            $payment,
            $order,
            $customerFactory,
            $customerRepository,
            $helperFactory,
            $modelFactory
        );
    }

    //########################################

    public function getChannelOrderNumber()
    {
        return $this->order->getEbayOrderId();
    }

    //########################################

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOrderNumberPrefix()
    {
        $prefix = $this->order->getEbayAccount()->getMagentoOrdersNumberRegularPrefix();

        if ($this->order->getEbayAccount()->isMagentoOrdersNumberMarketplacePrefixUsed()) {
            $prefix .= $this->order->getMagentoOrdersNumberMarketplacePrefix();
        }

        return $prefix;
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

            if ($customerDataObject->getId() === null) {
                throw new \Ess\M2ePro\Model\Exception(
                    'Customer with ID specified in eBay Account
                    Settings does not exist.'
                );
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

            $customerObject = $this->customerFactory->create()->getCollection()
                ->addAttributeToSelect(self::USER_ID_ATTRIBUTE_CODE)
                ->addAttributeToFilter(
                    'website_id',
                    $this->order->getEbayAccount()->getMagentoOrdersCustomerNewWebsiteId()
                )
                ->addAttributeToFilter(self::USER_ID_ATTRIBUTE_CODE, $this->order->getBuyerUserId())->getFirstItem();

            if (!empty($customerObject) && $customerObject->getId() !== null) {
                $customerBuilder->setData($customerInfo);
                $customerBuilder->updateAddress($customerObject);

                return $customerObject->getDataModel();
            }

            $customerObject->setWebsiteId($this->order->getEbayAccount()->getMagentoOrdersCustomerNewWebsiteId());
            $customerObject->loadByEmail($customerInfo['email']);

            if ($customerObject->getId() !== null) {
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

        return null;
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

        $addressData = [];

        $recipientNameParts = $this->getNameParts($rawAddressData['recipient_name']);
        $addressData['firstname'] = $recipientNameParts['firstname'];
        $addressData['lastname'] = $recipientNameParts['lastname'];
        $addressData['middlename'] = $recipientNameParts['middlename'];

        $customerNameParts = $this->getNameParts($rawAddressData['buyer_name']);
        $addressData['customer_firstname'] = $customerNameParts['firstname'];
        $addressData['customer_lastname'] = $customerNameParts['lastname'];
        $addressData['customer_middlename'] = $customerNameParts['middlename'];

        $addressData['email'] = $rawAddressData['email'];
        $addressData['country_id'] = $rawAddressData['country_id'];
        $addressData['region'] = $rawAddressData['region'];
        $addressData['region_id'] = $addressModel->getRegionId();
        $addressData['city'] = $rawAddressData['city'];
        $addressData['postcode'] = $rawAddressData['postcode'];
        $addressData['telephone'] = $rawAddressData['telephone'];
        $addressData['company'] = !empty($rawAddressData['company']) ? $rawAddressData['company'] : '';

        // Adding reference id into street array
        // ---------------------------------------
        $referenceId = '';
        $addressData['street'] = !empty($rawAddressData['street']) ? $rawAddressData['street'] : [];

        if ($this->order->isUseGlobalShippingProgram()) {
            $details = $this->order->getGlobalShippingDetails();
            isset($details['warehouse_address']['reference_id']) &&
            $referenceId = 'Ref #' . $details['warehouse_address']['reference_id'];
        }

        if ($this->order->isUseClickAndCollect()) {
            $details = $this->order->getClickAndCollectDetails();
            isset($details['reference_id']) && $referenceId = 'Ref #' . $details['reference_id'];
        }

        if ($this->order->isUseInStorePickup()) {
            $details = $this->order->getInStorePickupDetails();
            isset($details['reference_id']) && $referenceId = 'Ref #' . $details['reference_id'];
        }

        if (!empty($referenceId)) {
            if (count($addressData['street']) >= 2) {
                $addressData['street'] = [
                    $referenceId,
                    implode(' ', $addressData['street']),
                ];
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
        if ($this->order->getEbayAccount()->useMagentoOrdersShippingAddressAsBillingAlways()) {
            return parent::getAddressData();
        }

        if ($this->order->getEbayAccount()->useMagentoOrdersShippingAddressAsBillingIfSameCustomerAndRecipient() &&
            $this->order->getShippingAddress()->hasSameBuyerAndRecipient()
        ) {
            return parent::getAddressData();
        }

        $customerNameParts = $this->getNameParts($this->order->getBuyerName());

        return [
            'firstname'  => $customerNameParts['firstname'],
            'middlename' => $customerNameParts['middlename'],
            'lastname'   => $customerNameParts['lastname'],
            'country_id' => '',
            'region'     => '',
            'region_id'  => '',
            'city'       => 'eBay does not supply the complete billing Buyer information.',
            'postcode'   => '',
            'street'     => [],
            'company'    => ''
        ];
    }

    /**
     * @return bool
     */
    public function shouldIgnoreBillingAddressValidation()
    {
        if ($this->order->getEbayAccount()->useMagentoOrdersShippingAddressAsBillingAlways()) {
            return false;
        }

        if ($this->order->getEbayAccount()->useMagentoOrdersShippingAddressAsBillingIfSameCustomerAndRecipient() &&
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
        $paymentMethodTitle = $this->order->getPaymentMethod();
        $paymentMethodTitle == 'None' && $paymentMethodTitle = $this->getHelper('Module\Translation')->__(
            'Not Selected Yet'
        );

        $paymentData = [
            'method'                => $this->payment->getCode(),
            'component_mode'        => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'payment_method'        => $paymentMethodTitle,
            'channel_order_id'      => $this->order->getEbayOrderId(),
            'channel_final_fee'     => $this->convertPrice($this->order->getFinalFee()),
            'cash_on_delivery_cost' => $this->convertPrice($this->order->getCashOnDeliveryCost()),
            'transactions'          => $this->getPaymentTransactions(),
            'tax_id'                => $this->order->getBuyerTaxId(),
        ];

        return $paymentData;
    }

    /**
     * @return array
     */
    public function getPaymentTransactions()
    {
        /** @var \Ess\M2ePro\Model\Ebay\Order\ExternalTransaction[] $externalTransactions */
        $externalTransactions = $this->order->getExternalTransactionsCollection()->getItems();

        $paymentTransactions = [];
        foreach ($externalTransactions as $externalTransaction) {
            $paymentTransactions[] = [
                'transaction_id'   => $externalTransaction->getTransactionId(),
                'sum'              => $externalTransaction->getSum(),
                'fee'              => $externalTransaction->getFee(),
                'transaction_date' => $externalTransaction->getTransactionDate(),
            ];
        }

        return $paymentTransactions;
    }

    //########################################

    /**
     * @return array
     */
    public function getShippingData()
    {
        $additionalData = '';

        if ($this->order->isUseClickAndCollect() || $this->order->isUseInStorePickup()) {
            if ($this->order->isUseClickAndCollect()) {
                $additionalData .= 'Click And Collect | ';
                $details = $this->order->getClickAndCollectDetails();
            } else {
                $additionalData .= 'In Store Pickup | ';
                $details = $this->order->getInStorePickupDetails();
            }

            if (!empty($details['location_id'])) {
                $additionalData .= 'Store ID: ' . $details['location_id'] . ' | ';
            }

            if (!empty($details['reference_id'])) {
                $additionalData .= 'Reference ID: ' . $details['reference_id'] . ' | ';
            }

            if (!empty($details['delivery_date'])) {
                $additionalData .= 'Delivery Date: ' . $details['delivery_date'] . ' | ';
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

        if ($taxReference = $this->order->getTaxReference()) {
            $additionalData .= 'IOSS/OSS Number: ' . $taxReference . ' | ';
        }

        if (!empty($additionalData)) {
            $additionalData = ' | ' . $additionalData;
        }

        $shippingMethod = $this->order->getShippingService();

        if ($this->order->isUseGlobalShippingProgram()) {
            $globalShippingDetails = $this->order->getGlobalShippingDetails();
            $globalShippingDetails = $globalShippingDetails['service_details'];

            if (!empty($globalShippingDetails['service_details']['service'])) {
                $shippingMethod = $globalShippingDetails['service_details']['service'];
            }
        }

        return [
            'carrier_title'   => $this->getHelper('Module\Translation')->__('eBay Shipping'),
            'shipping_method' => $shippingMethod . $additionalData,
            'shipping_price'  => $this->getBaseShippingPrice()
        ];
    }

    /**
     * @return float
     */
    protected function getShippingPrice()
    {
        if ($this->order->isUseGlobalShippingProgram()) {
            $globalShippingDetails = $this->order->getGlobalShippingDetails();
            $price = (float)$globalShippingDetails['service_details']['price'];
        } else {
            $price = $this->order->getShippingPrice();
        }

        if ($this->isTaxModeNone() && !$this->isShippingPriceIncludeTax()) {
            $taxAmount = $this->taxCalculation->calcTaxAmount(
                $price,
                $this->getShippingPriceTaxRate(),
                false,
                false
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
        $comments = [];

        if ($this->order->isUseGlobalShippingProgram()) {
            $comments[] = '<b>' .
                $this->getHelper('Module\Translation')->__('Global Shipping Program is used for this Order') .
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
     * @return bool
     */
    public function isTaxModeNone()
    {
        if ($this->order->isUseGlobalShippingProgram()) {
            return true;
        }

        return $this->order->getEbayAccount()->isMagentoOrdersTaxModeNone();
    }

    //########################################
}
