<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order\Item;

use Ess\M2ePro\Model\Ebay\Order\Item as EbayItem;
use Ess\M2ePro\Model\Amazon\Order\Item as AmazonItem;
use Ess\M2ePro\Model\Walmart\Order\Item as WalmartItem;

/**
 * Class \Ess\M2ePro\Model\Order\Item\ProxyObject
 */
abstract class ProxyObject extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var EbayItem|AmazonItem|WalmartItem */
    protected $item;

    protected $qty;

    protected $price;

    protected $subtotal;

    protected $additionalData = [];

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel $item,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->item = $item;
        $this->subtotal = $this->getOriginalPrice() * $this->getOriginalQty();
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    abstract public function getOriginalPrice();

    abstract public function getOriginalQty();

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Order\ProxyObject
     */
    public function getProxyOrder()
    {
        return $this->item->getParentObject()->getOrder()->getProxy();
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Order\Item\ProxyObject $that
     * @return bool
     */
    public function equals(\Ess\M2ePro\Model\Order\Item\ProxyObject $that)
    {
        if ($this->getProductId() === null || $that->getProductId() === null) {
            return false;
        }

        if ($this->getProductId() != $that->getProductId()) {
            return false;
        }

        $thisOptions = $this->getOptions();
        $thatOptions = $that->getOptions();

        $thisOptionsKeys = array_keys($thisOptions);
        $thatOptionsKeys = array_keys($thatOptions);

        $thisOptionsValues = array_values($thisOptions);
        $thatOptionsValues = array_values($thatOptions);

        if (count($thisOptions) != count($thatOptions)
            || count(array_diff($thisOptionsKeys, $thatOptionsKeys)) > 0
            || count(array_diff($thisOptionsValues, $thatOptionsValues)) > 0
        ) {
            return false;
        }

        // grouped products have no options, that's why we have to compare associated products
        $thisAssociatedProducts = $this->getAssociatedProducts();
        $thatAssociatedProducts = $that->getAssociatedProducts();

        if (count($thisAssociatedProducts) != count($thatAssociatedProducts)
            || count(array_diff($thisAssociatedProducts, $thatAssociatedProducts)) > 0
        ) {
            return false;
        }

        return true;
    }

    public function merge(\Ess\M2ePro\Model\Order\Item\ProxyObject $that)
    {
        $this->setQty($this->getQty() + $that->getOriginalQty());
        $this->subtotal += $that->getOriginalPrice() * $that->getOriginalQty();

        // merge additional data
        // ---------------------------------------
        $thisAdditionalData = $this->getAdditionalData();
        $thatAdditionalData = $that->getAdditionalData();

        $identifier = \Ess\M2ePro\Helper\Data::CUSTOM_IDENTIFIER;

        $thisAdditionalData[$identifier]['items'][] = $thatAdditionalData[$identifier]['items'][0];

        $this->additionalData = $thisAdditionalData;
        // ---------------------------------------
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function pretendedToBeSimple()
    {
        return $this->item->getParentObject()->pretendedToBeSimple();
    }

    //########################################

    public function getProduct()
    {
        return $this->item->getParentObject()->getProduct();
    }

    public function getProductId()
    {
        return $this->item->getParentObject()->getProductId();
    }

    public function getMagentoProduct()
    {
        return $this->item->getParentObject()->getMagentoProduct();
    }

    //########################################

    public function getOptions()
    {
        return $this->item->getParentObject()->getAssociatedOptions();
    }

    public function getAssociatedProducts()
    {
        return $this->item->getParentObject()->getAssociatedProducts();
    }

    //########################################

    public function getBasePrice()
    {
        return $this->getProxyOrder()->convertPriceToBase($this->getPrice());
    }

    /**
     * @param float $price
     * @return $this
     */
    public function setPrice($price)
    {
        if ($price <= 0) {
            throw new \InvalidArgumentException('Price cannot be less than zero.');
        }

        $this->price = $price;

        return $this;
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        if ($this->price !== null) {
            return $this->price;
        }

        return $this->subtotal / $this->getQty();
    }

    public function setQty($qty)
    {
        if ((int)$qty <= 0) {
            throw new \InvalidArgumentException('QTY cannot be less than zero.');
        }

        $this->qty = (int)$qty;

        return $this;
    }

    public function getQty()
    {
        if ($this->qty !== null) {
            return $this->qty;
        }
        return $this->getOriginalQty();
    }

    //########################################

    public function hasTax()
    {
        return $this->getProxyOrder()->hasTax();
    }

    public function isSalesTax()
    {
        return $this->getProxyOrder()->isSalesTax();
    }

    public function isVatTax()
    {
        return $this->getProxyOrder()->isVatTax();
    }

    public function getTaxRate()
    {
        return $this->getProxyOrder()->getProductPriceTaxRate();
    }

    //########################################

    public function getWasteRecyclingFee()
    {
        return 0.0;
    }

    //########################################

    public function getGiftMessage()
    {
        return null;
    }

    //########################################

    abstract public function getAdditionalData();

    //########################################
}
