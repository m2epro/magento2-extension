<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Order;

/**
 * Class \Ess\M2ePro\Model\Walmart\Order\ProxyObject
 */
class ProxyObject extends \Ess\M2ePro\Model\Order\ProxyObject
{
    /** @var \Ess\M2ePro\Model\Walmart\Order */
    protected $order;

    /** @var \Ess\M2ePro\Model\Walmart\Order\Item\ProxyObject[] */
    protected $removedProxyItems = [];

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
    ) {
        $this->payment = $payment;
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
        if ($this->order->getWalmartAccount()->isMagentoOrdersCustomerPredefined() ||
            $this->order->getWalmartAccount()->isMagentoOrdersCustomerNew()) {
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
        return $this->order->getWalmartAccount()->isMagentoOrdersNumberSourceChannel();
    }

    /**
     * @return bool
     */
    public function isOrderNumberPrefixSourceMagento()
    {
        return $this->order->getWalmartAccount()->isMagentoOrdersNumberSourceMagento();
    }

    /**
     * @return mixed
     */
    public function getChannelOrderNumber()
    {
        return $this->order->getWalmartOrderId();
    }

    /**
     * @return null|string
     */
    public function getOrderNumberPrefix()
    {
        if (!$this->order->getWalmartAccount()->isMagentoOrdersNumberPrefixEnable()) {
            return '';
        }

        return $this->order->getWalmartAccount()->getMagentoOrdersNumberRegularPrefix();
    }

    //########################################

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface|\Magento\Customer\Model\Data\Customer|null
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomer()
    {

        if ($this->order->getWalmartAccount()->isMagentoOrdersCustomerPredefined()) {
            $customerDataObject = $this->customerRepository->getById(
                $this->order->getWalmartAccount()->getMagentoOrdersCustomerId()
            );

            if ($customerDataObject->getId() === null) {
                throw new \Ess\M2ePro\Model\Exception('Customer with ID specified in Walmart Account
                    Settings does not exist.');
            }

            return $customerDataObject;
        }

        /** @var $customerBuilder \Ess\M2ePro\Model\Magento\Customer */
        $customerBuilder = $this->modelFactory->getObject('Magento\Customer');

        if ($this->order->getWalmartAccount()->isMagentoOrdersCustomerNew()) {
            $customerInfo = $this->getAddressData();

            $customerObject = $this->customerFactory->create();
            $customerObject->setWebsiteId($this->order->getWalmartAccount()->getMagentoOrdersCustomerNewWebsiteId());
            $customerObject->loadByEmail($customerInfo['email']);

            if ($customerObject->getId() !== null) {
                $customerBuilder->setData($customerInfo);
                $customerBuilder->updateAddress($customerObject);

                return $customerObject->getDataModel();
            }

            $customerInfo['website_id'] = $this->order->getWalmartAccount()->getMagentoOrdersCustomerNewWebsiteId();
            $customerInfo['group_id'] = $this->order->getWalmartAccount()->getMagentoOrdersCustomerNewGroupId();

            $customerBuilder->setData($customerInfo);
            $customerBuilder->buildCustomer();
            $customerBuilder->getCustomer()->save();

            return $customerBuilder->getCustomer()->getDataModel();
        }

        return null;
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
        $paymentData = [
            'method'                => $this->payment->getCode(),
            'component_mode'        => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'payment_method'        => '',
            'channel_order_id'      => $this->order->getWalmartOrderId(),
            'channel_final_fee'     => 0,
            'cash_on_delivery_cost' => 0,
            'transactions'          => []
        ];

        return $paymentData;
    }

    //########################################

    /**
     * @return array
     */
    public function getShippingData()
    {
        $shippingData = [
            'shipping_method' => $this->order->getShippingService(),
            'shipping_price'  => $this->getBaseShippingPrice(),
            'carrier_title'   => $this->getHelper('Module\Translation')->__('Walmart Shipping')
        ];

        return $shippingData;
    }

    /**
     * @return float
     */
    protected function getShippingPrice()
    {
        $price = $this->order->getShippingPrice();

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
        $comments = [];

        // Removed Order Items
        // ---------------------------------------
        if (!empty($this->removedProxyItems)) {
            $comment = '<u>' .
                $this->getHelper('Module\Translation')->__(
                    'The following SKUs have zero price and can not be included in Magento order line items'
                ) .
                ':</u><br/>';

            $zeroItems = [];
            foreach ($this->removedProxyItems as $item) {
                $productSku = $item->getMagentoProduct()->getSku();
                $qtyPurchased = $item->getQty();

                $zeroItems[] = "<b>{$productSku}</b>: {$qtyPurchased} QTY";
            }

            $comments[] = $comment . implode('<br/>,', $zeroItems);
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
            ->getGroupValue('/walmart/order/tax/product_price/', 'is_include_tax');

        if ($configValue !== null) {
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
            ->getGroupValue('/walmart/order/tax/shipping_price/', 'is_include_tax');

        if ($configValue !== null) {
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
        return $this->order->getWalmartAccount()->isMagentoOrdersTaxModeNone();
    }

    /**
     * @return bool
     */
    public function isTaxModeMagento()
    {
        return $this->order->getWalmartAccount()->isMagentoOrdersTaxModeMagento();
    }

    /**
     * @return bool
     */
    public function isTaxModeChannel()
    {
        return $this->order->getWalmartAccount()->isMagentoOrdersTaxModeChannel();
    }

    //########################################
}
