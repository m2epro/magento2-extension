<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order;

abstract class Proxy extends \Ess\M2ePro\Model\AbstractModel
{
    const CHECKOUT_GUEST    = 'guest';
    const CHECKOUT_REGISTER = 'register';

    protected $currency;
    /** @var $order \Ess\M2ePro\Model\Ebay\Order|\Ess\M2ePro\Model\Amazon\Order */
    protected $order = NULL;

    protected $items = NULL;

    /** @var $store \Magento\Store\Model\Store */
    protected $store = NULL;

    protected $addressData = array();

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Currency $currency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel $order,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->currency = $currency;
        $this->order = $order;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order\Item\Proxy[]
     */
    public function getItems()
    {
        if (is_null($this->items)) {
            $items = array();

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
     * @param \Ess\M2ePro\Model\Order\Item\Proxy[] $items
     * @return \Ess\M2ePro\Model\Order\Item\Proxy[]
     */
    protected function mergeItems(array $items)
    {
        $unsetItems = array();

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
        if (is_null($this->store)) {
            throw new \Ess\M2ePro\Model\Exception('Store is not set.');
        }

        return $this->store;
    }

    //########################################

    abstract public function getCheckoutMethod();

    /**
     * @return bool
     */
    public function isCheckoutMethodGuest()
    {
        return $this->getCheckoutMethod() == self::CHECKOUT_GUEST;
    }

    //########################################

    abstract public function isOrderNumberPrefixSourceMagento();

    abstract public function isOrderNumberPrefixSourceChannel();

    abstract public function getChannelOrderNumber();

    abstract public function getOrderNumberPrefix();

    //########################################

    abstract public function getBuyerEmail();

    //########################################

    /**
     * @return \Magento\Customer\Model\Data\Customer
     */
    abstract public function getCustomer();

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

    //########################################

    /**
     * @return array
     */
    public function getAddressData()
    {
        if (empty($this->addressData)) {
            $rawAddressData = $this->order->getShippingAddress()->getRawData();

            $recipientNameParts = $this->getNameParts($rawAddressData['recipient_name']);
            $this->addressData['firstname'] = $recipientNameParts['firstname'];
            $this->addressData['lastname'] = $recipientNameParts['lastname'];

            $customerNameParts = $this->getNameParts($rawAddressData['buyer_name']);
            $this->addressData['customer_firstname'] = $customerNameParts['firstname'];
            $this->addressData['customer_lastname'] = $customerNameParts['lastname'];

            $this->addressData['email'] = $rawAddressData['email'];
            $this->addressData['country_id'] = $rawAddressData['country_id'];
            $this->addressData['region'] = $rawAddressData['region'];
            $this->addressData['region_id'] = $this->order->getShippingAddress()->getRegionId();
            $this->addressData['city'] = $rawAddressData['city'];
            $this->addressData['postcode'] = $rawAddressData['postcode'];
            $this->addressData['telephone'] = $rawAddressData['telephone'];
            $this->addressData['street'] = !empty($rawAddressData['street']) ?
                                            implode('; ', $rawAddressData['street']) : '';
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

    protected function getNameParts($fullName)
    {
        $fullName = trim($fullName);

        $spacePosition = strpos($fullName, ' ');
        $spacePosition === false && $spacePosition = strlen($fullName);

        $firstName = trim(substr($fullName, 0, $spacePosition));
        $lastName = trim(substr($fullName, $spacePosition + 1));

        return array(
            'firstname' => $firstName ? $firstName : 'N/A',
            'lastname'  => $lastName ? $lastName : 'N/A'
        );
    }

    //########################################

    abstract public function getCurrency();

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

    abstract public function isProductPriceIncludeTax();

    abstract public function isShippingPriceIncludeTax();

    // ---------------------------------------

    abstract public function isTaxModeNone();

    abstract public function isTaxModeChannel();

    abstract public function isTaxModeMagento();

    /**
     * @return bool
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
        return array();
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
            return array();
        }

        $comments = array();

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