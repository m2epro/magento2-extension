<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order;

use Ess\M2ePro\Model\Amazon\Order as AmazonOrder;
use Ess\M2ePro\Model\Ebay\Order as EbayOrder;
use Ess\M2ePro\Model\Walmart\Order as WalmartOrder;

/**
 * Class Ess\M2ePro\Model\Order\ProxyObject
 */
abstract class ProxyObject extends \Ess\M2ePro\Model\AbstractModel
{
    const CHECKOUT_GUEST    = 'guest';
    const CHECKOUT_REGISTER = 'register';

    /** @var \Ess\M2ePro\Model\Currency */
    protected $currency;

    /** @var \Ess\M2ePro\Model\Magento\Payment */
    protected $payment;

    /** @var EbayOrder|AmazonOrder|WalmartOrder */
    protected $order;

    /** @var \Magento\Customer\Model\CustomerFactory */
    protected $customerFactory;

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    protected $customerRepository;

    /** @var \Ess\M2ePro\Model\Order\Item\ProxyObject[] */
    protected $items;

    /** @var \Magento\Store\Model\Store */
    protected $store;

    /** @var array */
    protected $addressData = [];

    /** @var \Magento\Customer\Model\Options */
    protected $options;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Currency $currency,
        \Ess\M2ePro\Model\Magento\Payment $payment,
        \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel $order,
        \Magento\Customer\Model\Options $options,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->currency = $currency;
        $this->payment = $payment;
        $this->order = $order;
        $this->options = $options;

        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order\Item\ProxyObject[]
     */
    public function getItems()
    {
        if ($this->items === null) {
            $items = [];

            foreach ($this->order->getParentObject()->getItemsCollection()->getItems() as $item) {
                $proxyItem = $item->getProxy();
                if ($proxyItem->getQty() <= 0) {
                    continue;
                }

                $items[] = $proxyItem;
            }

            $this->items = $this->mergeItems($items);
        }

        return $this->items;
    }

    /**
     * Order may have multiple items ordered, but some of them may be mapped to single product in magento.
     * We have to merge them to avoid qty and price calculation issues.
     *
     * @param \Ess\M2ePro\Model\Order\Item\ProxyObject[] $items
     * @return \Ess\M2ePro\Model\Order\Item\ProxyObject[]
     */
    protected function mergeItems(array $items)
    {
        $unsetItems = [];

        foreach ($items as $key => &$item) {
            if (in_array($key, $unsetItems)) {
                continue;
            }

            foreach ($items as $nestedKey => $nestedItem) {
                if ($key == $nestedKey) {
                    continue;
                }

                if (!$item->equals($nestedItem)) {
                    continue;
                }

                $item->merge($nestedItem);

                $unsetItems[] = $nestedKey;
            }
        }

        foreach ($unsetItems as $key) {
            unset($items[$key]);
        }

        return $items;
    }

    //########################################

    /**
     * @param \Magento\Store\Api\Data\StoreInterface $store
     * @return $this
     */
    public function setStore(\Magento\Store\Api\Data\StoreInterface $store)
    {
        $this->store = $store;
        return $this;
    }

    /**
     * @return \Magento\Store\Model\Store
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getStore()
    {
        if ($this->store === null) {
            throw new \Ess\M2ePro\Model\Exception('Store is not set.');
        }

        return $this->store;
    }

    //########################################

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getCheckoutMethod()
    {
        if ($this->order->getParentObject()->getAccount()->getChildObject()->isMagentoOrdersCustomerPredefined() ||
            $this->order->getParentObject()->getAccount()->getChildObject()->isMagentoOrdersCustomerNew()) {
            return self::CHECKOUT_REGISTER;
        }

        return self::CHECKOUT_GUEST;
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isCheckoutMethodGuest()
    {
        return $this->getCheckoutMethod() == self::CHECKOUT_GUEST;
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isOrderNumberPrefixSourceMagento()
    {
        return $this->order->getParentObject()->getAccount()->getChildObject()->isMagentoOrdersNumberSourceMagento();
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isOrderNumberPrefixSourceChannel()
    {
        return $this->order->getParentObject()->getAccount()->getChildObject()->isMagentoOrdersNumberSourceChannel();
    }

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOrderNumberPrefix()
    {
        return $this->order->getParentObject()->getAccount()->getChildObject()->getMagentoOrdersNumberRegularPrefix();
    }

    abstract public function getChannelOrderNumber();

    //########################################

    public function isMagentoOrdersCustomerNewNotifyWhenOrderCreated()
    {
        return $this->order->getParentObject()->getAccount()->getChildObject()
            ->isMagentoOrdersCustomerNewNotifyWhenOrderCreated();
    }

    /**
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getCustomer()
    {
        $accountModel = $this->order->getParentObject()->getAccount()->getChildObject();

        if ($accountModel->isMagentoOrdersCustomerPredefined()) {
            $customerDataObject = $this->customerRepository->getById(
                $accountModel->getMagentoOrdersCustomerId()
            );

            if ($customerDataObject->getId() === null) {
                throw new \Ess\M2ePro\Model\Exception(
                    "Customer with ID specified in {$this->order->getParentObject()->getComponentTitle()} Account
                    Settings does not exist."
                );
            }

            return $customerDataObject;
        }

        /** @var $customerBuilder \Ess\M2ePro\Model\Magento\Customer */
        $customerBuilder = $this->modelFactory->getObject('Magento\Customer');

        if ($accountModel->isMagentoOrdersCustomerNew()) {
            $customerInfo = $this->getAddressData();

            $customerObject = $this->customerFactory->create();
            $customerObject->setWebsiteId($accountModel->getMagentoOrdersCustomerNewWebsiteId());
            $customerObject->loadByEmail($customerInfo['email']);

            if ($customerObject->getId() !== null) {
                $customerBuilder->setData($customerInfo);
                $customerBuilder->updateAddress($customerObject);

                return $customerObject->getDataModel();
            }

            $customerInfo['website_id'] = $accountModel->getMagentoOrdersCustomerNewWebsiteId();
            $customerInfo['group_id'] = $accountModel->getMagentoOrdersCustomerNewGroupId();

            $customerBuilder->setData($customerInfo);
            $customerBuilder->buildCustomer();
            $customerBuilder->getCustomer()->save();

            return $customerBuilder->getCustomer()->getDataModel();
        }

        return null;
    }

    //########################################

    public function getCustomerFirstName()
    {
        $addressData = $this->getAddressData();

        return $addressData['firstname'];
    }

    public function getCustomerLastName()
    {
        $addressData = $this->getAddressData();

        return $addressData['lastname'];
    }

    public function getBuyerEmail()
    {
        $addressData = $this->getAddressData();

        return $addressData['email'];
    }

    //########################################

    /**
     * @return array
     */
    public function getAddressData()
    {
        if (empty($this->addressData)) {
            $rawAddressData = $this->order->getShippingAddress()->getRawData();

            $recipientNameParts = $this->getNameParts($rawAddressData['recipient_name']);
            $this->addressData['prefix'] = $recipientNameParts['prefix'];
            $this->addressData['firstname'] = $recipientNameParts['firstname'];
            $this->addressData['middlename'] = $recipientNameParts['middlename'];
            $this->addressData['lastname'] = $recipientNameParts['lastname'];
            $this->addressData['suffix'] = $recipientNameParts['suffix'];

            $customerNameParts = $this->getNameParts($rawAddressData['buyer_name']);
            $this->addressData['customer_prefix'] = $customerNameParts['prefix'];
            $this->addressData['customer_firstname'] = $customerNameParts['firstname'];
            $this->addressData['customer_middlename'] = $customerNameParts['middlename'];
            $this->addressData['customer_lastname'] = $customerNameParts['lastname'];
            $this->addressData['customer_suffix'] = $customerNameParts['suffix'];

            $this->addressData['email'] = $rawAddressData['email'];
            $this->addressData['country_id'] = $rawAddressData['country_id'];
            $this->addressData['region'] = $rawAddressData['region'];
            $this->addressData['region_id'] = $this->order->getShippingAddress()->getRegionId();
            $this->addressData['city'] = $rawAddressData['city'];
            $this->addressData['postcode'] = $rawAddressData['postcode'];
            $this->addressData['telephone'] = $rawAddressData['telephone'];
            $this->addressData['street'] = !empty($rawAddressData['street']) ? $rawAddressData['street'] : '';
            $this->addressData['company'] = !empty($rawAddressData['company']) ? $rawAddressData['company'] : '';
            $this->addressData['save_in_address_book'] = 0;
        }

        return $this->addressData;
    }

    /**
     * @return array
     */
    public function getBillingAddressData()
    {
        return $this->getAddressData();
    }

    /**
     * @return bool
     */
    public function shouldIgnoreBillingAddressValidation()
    {
        return false;
    }

    //########################################

    /**
     * @param $fullName
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     */
    protected function getNameParts($fullName)
    {
        $fullName = trim($fullName);
        $parts = explode(' ', $fullName);

        $currentInfo = [
            'prefix'     => null,
            'middlename' => null,
            'suffix'     => null
        ];

        if (count($parts) > 2) {
            $prefixOptions = $this->options->getNamePrefixOptions($this->getStore());
            if (is_array($prefixOptions) && in_array($parts[0], $prefixOptions)) {
                $currentInfo['prefix'] = array_shift($parts);
            }
        }

        $partsCount = count($parts);
        if ($partsCount > 2) {
            $suffixOptions = $this->options->getNameSuffixOptions($this->getStore());
            if (is_array($suffixOptions) && in_array($parts[$partsCount - 1], $suffixOptions)) {
                $currentInfo['suffix'] = array_pop($parts);
            }
        }

        $partsCount = count($parts);
        if ($partsCount > 2) {
            $middleName = array_slice($parts, 1, $partsCount - 2);
            $currentInfo['middlename'] = implode(' ', $middleName);
            $parts = [$parts[0], $parts[$partsCount - 1]];
        }

        $currentInfo['firstname'] = isset($parts[0]) ? $parts[0] : 'N/A';
        $currentInfo['lastname'] = isset($parts[1]) ? $parts[1] : 'N/A';

        return $currentInfo;
    }

    //########################################

    /**
     * @return mixed
     */
    public function getCurrency()
    {
        return $this->order->getCurrency();
    }

    public function convertPrice($price)
    {
        return $this->currency->convertPrice($price, $this->getCurrency(), $this->getStore());
    }

    public function convertPriceToBase($price)
    {
        return $this->currency->convertPriceToBaseCurrency($price, $this->getCurrency(), $this->getStore());
    }

    //########################################

    abstract public function getPaymentData();

    //########################################

    abstract public function getShippingData();

    abstract protected function getShippingPrice();

    protected function getBaseShippingPrice()
    {
        return $this->convertPriceToBase($this->getShippingPrice());
    }

    //########################################

    abstract public function hasTax();

    abstract public function isSalesTax();

    abstract public function isVatTax();

    // ---------------------------------------

    abstract public function getProductPriceTaxRate();

    abstract public function getShippingPriceTaxRate();

    // ---------------------------------------

    /**
     * @return bool|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isProductPriceIncludeTax()
    {
        return $this->isPriceIncludeTax('product');
    }

    /**
     * @return bool|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isShippingPriceIncludeTax()
    {
        return $this->isPriceIncludeTax('shipping');
    }

    /**
     * @param $priceType
     * @return bool|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     *
     * List of config keys for comfortable search:
     * /ebay/order/tax/product_price/
     * /ebay/order/tax/shipping_price/
     * /amazon/order/tax/product_price/
     * /amazon/order/tax/shipping_price/
     * /walmart/order/tax/product_price/
     * /walmart/order/tax/shipping_price/
     */
    protected function isPriceIncludeTax($priceType)
    {
        $componentMode = $this->order->getParentObject()->getComponentMode();
        $configValue = $this->getHelper('Module')
            ->getConfig()
            ->getGroupValue("/{$componentMode}/order/tax/{$priceType}_price/", 'is_include_tax');

        if ($configValue !== null) {
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
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isTaxModeNone()
    {
        return $this->order->getParentObject()->getAccount()->getChildObject()->isMagentoOrdersTaxModeNone();
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isTaxModeChannel()
    {
        return $this->order->getParentObject()->getAccount()->getChildObject()->isMagentoOrdersTaxModeChannel();
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isTaxModeMagento()
    {
        return $this->order->getParentObject()->getAccount()->getChildObject()->isMagentoOrdersTaxModeMagento();
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isTaxModeMixed()
    {
        return !$this->isTaxModeNone() &&
        !$this->isTaxModeChannel() &&
        !$this->isTaxModeMagento();
    }

    //########################################

    public function getWasteRecyclingFee()
    {
        $resultFee = 0.0;

        foreach ($this->getItems() as $item) {
            $resultFee += $item->getWasteRecyclingFee();
        }

        return $resultFee;
    }

    //########################################

    /**
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function initializeShippingMethodDataPretendedToBeSimple()
    {
        foreach ($this->order->getParentObject()->getItemsCollection() as $item) {
            /** @var \Ess\M2ePro\Model\Order\Item $item */
            if (!$item->pretendedToBeSimple()) {
                continue;
            }

            $shippingItems = [];
            $product = $item->getMagentoProduct();
            foreach ($product->getTypeInstance()->getAssociatedProducts($product->getProduct()) as $associatedProduct) {
                /** @var \Magento\Catalog\Model\Product $associatedProduct */
                if ($associatedProduct->getQty() <= 0) { // skip product if default qty zero
                    continue;
                }

                $total = (int)($associatedProduct->getQty() * $item->getChildObject()->getQtyPurchased());
                $shippingItems[$associatedProduct->getId()]['total'] = $total;
                $shippingItems[$associatedProduct->getId()]['shipped'] = [];
            }

            $shippingInfo = [];
            $shippingInfo['items'] = $shippingItems;
            $shippingInfo['send'] = $item->getChildObject()->getQtyPurchased();

            $additionalData = $item->getAdditionalData();
            $additionalData['shipping_info'] = $shippingInfo;
            $item->setSettings('additional_data', $additionalData);
            $item->save();
        }
    }

    //########################################

    /**
     * @return array
     */
    public function getComments()
    {
        return array_merge($this->getGeneralComments(), $this->getChannelComments());
    }

    /**
     * @return array
     */
    public function getChannelComments()
    {
        return [];
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getGeneralComments()
    {
        $store = $this->getStore();

        $currencyConvertRate = $this->currency->getConvertRateFromBase($this->getCurrency(), $store, 4);

        if ($this->currency->isBase($this->getCurrency(), $store)) {
            return [];
        }

        $comments = [];

        if (!$this->currency->isAllowed($this->getCurrency(), $store)) {
            $comments[] = <<<COMMENT
<b>Attention!</b> The Order Prices are incorrect.
Conversion was not performed as "{$this->getCurrency()}" Currency is not enabled.
Default Currency "{$store->getBaseCurrencyCode()}" was used instead.
Please, enable Currency in System > Configuration > Currency Setup.
COMMENT;
        } elseif ($currencyConvertRate == 0) {
            $comments[] = <<<COMMENT
<b>Attention!</b> The Order Prices are incorrect.
Conversion was not performed as there's no rate for "{$this->getCurrency()}".
Default Currency "{$store->getBaseCurrencyCode()}" was used instead.
Please, add Currency convert rate in System > Manage Currency > Rates.
COMMENT;
        } else {
            $comments[] = <<<COMMENT
Because the Order Currency is different from the Store Currency,
the conversion from <b>"{$this->getCurrency()}" to "{$store->getBaseCurrencyCode()}"</b> was performed
using <b>{$currencyConvertRate}</b> as a rate.
COMMENT;
        }

        return $comments;
    }

    //########################################
}
