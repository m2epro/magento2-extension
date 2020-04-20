<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Listing\Product\Variation getParentObject()
 */
namespace Ess\M2ePro\Model\Ebay\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Variation
 */
class Variation extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Ebay\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\Variation');
    }

    //########################################

    public function afterSave()
    {
        $this->getHelper('Data_Cache_Runtime')->removeTagValues(
            "listing_product_{$this->getListingProduct()->getId()}_variations"
        );

        return parent::afterSave();
    }

    public function beforeDelete()
    {
        $this->getHelper('Data_Cache_Runtime')->removeTagValues(
            "listing_product_{$this->getListingProduct()->getId()}_variations"
        );

        return parent::beforeDelete();
    }

    public function delete()
    {
        if ($this->getId() === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Method require loaded instance first');
        }

        if ($this->isLocked()) {
            return false;
        }

        if ($this->getEbayAccount()->isPickupStoreEnabled()) {
            $this->activeRecordFactory->getObject('Ebay_Listing_Product_PickupStore')->getResource()
                ->processDeletedVariation(
                    $this->getParentObject()
                );
        }

        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    public function getListing()
    {
        return $this->getParentObject()->getListing();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing
     */
    public function getEbayListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Listing\Product
     */
    public function getListingProduct()
    {
        return $this->getParentObject()->getListingProduct();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product
     */
    public function getEbayListingProduct()
    {
        return $this->getListingProduct()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        return $this->getParentObject()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Account
     */
    public function getEbayAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        return $this->getParentObject()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Marketplace
     */
    public function getEbayMarketplace()
    {
        return $this->getMarketplace()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        return $this->getEbayListingProduct()->getSellingFormatTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\SellingFormat
     */
    public function getEbaySellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Synchronization
     */
    public function getSynchronizationTemplate()
    {
        return $this->getEbayListingProduct()->getSynchronizationTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Synchronization
     */
    public function getEbaySynchronizationTemplate()
    {
        return $this->getSynchronizationTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     */
    public function getDescriptionTemplate()
    {
        return $this->getEbayListingProduct()->getDescriptionTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Description
     */
    public function getEbayDescriptionTemplate()
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    public function getPaymentTemplate()
    {
        return $this->getEbayListingProduct()->getPaymentTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
     */
    public function getReturnTemplate()
    {
        return $this->getEbayListingProduct()->getReturnTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping
     */
    public function getShippingTemplate()
    {
        return $this->getEbayListingProduct()->getShippingTemplate();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @param bool $tryToGetFromStorage
     * @return \Ess\M2ePro\Model\Listing\Product\Variation\Option[]
     */
    public function getOptions($asObjects = false, array $filters = [], $tryToGetFromStorage = true)
    {
        return $this->getParentObject()->getOptions($asObjects, $filters, $tryToGetFromStorage);
    }

    //########################################

    public function getOnlineSku()
    {
        return $this->getData('online_sku');
    }

    /**
     * @return float
     */
    public function getOnlinePrice()
    {
        return (float)$this->getData('online_price');
    }

    /**
     * @return int
     */
    public function getOnlineQty()
    {
        return (int)$this->getData('online_qty');
    }

    /**
     * @return int
     */
    public function getOnlineQtySold()
    {
        return (int)$this->getData('online_qty_sold');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isAdd()
    {
        return (bool)$this->getData('add');
    }

    /**
     * @return bool
     */
    public function isDelete()
    {
        return (bool)$this->getData('delete');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getStatus()
    {
        return (int)$this->getData('status');
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        switch ($status) {
            case \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED:
                $status = $this->calculateStatusByQty();
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN:
                $status = $this->calculateStatusByQty();
                if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                    $status = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
                }
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD:
                $status = $this->calculateStatusByQty();
                if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                    $status = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
                }
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED:
                $status = $this->calculateStatusByQty();
                if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                    $status = \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
                }
                break;

            case \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED:
                $status = $this->calculateStatusByQty();
                if ($status == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                    $status = \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED;
                }
                break;
        }

        $this->setData('status', $status);
        $this->getParentObject()->save();
    }

    // ---------------------------------------

    /**
     * @return int
     */
    private function calculateStatusByQty()
    {
        if ($this->getData('online_qty') === null) {
            return \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED;
        }

        if ($this->getOnlineQty() == 0) {
            return \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN;
        } elseif ($this->getOnlineQty() <= $this->getOnlineQtySold()) {
            return \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD;
        }

        return \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
    }

    //########################################

    /**
     * @return bool
     */
    public function isNotListed()
    {
        return $this->getStatus() == \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED;
    }

    /**
     * @return bool
     */
    public function isUnknown()
    {
        return $this->getStatus() == \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN;
    }

    /**
     * @return bool
     */
    public function isBlocked()
    {
        return $this->getStatus() == \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isListed()
    {
        return $this->getStatus() == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED;
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return $this->getStatus() == \Ess\M2ePro\Model\Listing\Product::STATUS_HIDDEN;
    }

    /**
     * @return bool
     */
    public function isSold()
    {
        return $this->getStatus() == \Ess\M2ePro\Model\Listing\Product::STATUS_SOLD;
    }

    /**
     * @return bool
     */
    public function isStopped()
    {
        return $this->getStatus() == \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED;
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        return $this->getStatus() == \Ess\M2ePro\Model\Listing\Product::STATUS_FINISHED;
    }

    //########################################

    /**
     * @return string
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getSku()
    {
        if ($this->isDelete()) {
            return '';
        }

        $sku = '';

        // Options Models
        $options = $this->getOptions(true);

        // Configurable, Grouped product
        if ($this->getListingProduct()->getMagentoProduct()->isConfigurableType() ||
            $this->getListingProduct()->getMagentoProduct()->isGroupedType()) {
            foreach ($options as $option) {
                /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */
                $sku = $option->getChildObject()->getSku();
                break;
            }

        // Bundle product
        } elseif ($this->getListingProduct()->getMagentoProduct()->isBundleType()) {
            foreach ($options as $option) {
                /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */
                $sku != '' && $sku .= '-';
                $sku .= $option->getChildObject()->getSku();
            }

        // Simple with options product
        } elseif ($this->getListingProduct()->getMagentoProduct()->isSimpleTypeWithCustomOptions()) {
            foreach ($options as $option) {
                /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */
                $sku != '' && $sku .= '-';
                $tempSku = $option->getChildObject()->getSku();
                if ($tempSku == '') {
                    $sku .= $this->getHelper('Data')->convertStringToSku($option->getOption());
                } else {
                    $sku .= $tempSku;
                }
            }

            // Downloadable with separated links product
        } elseif ($this->getListingProduct()->getMagentoProduct()->isDownloadableTypeWithSeparatedLinks()) {

            /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */

            $option = reset($options);
            $sku = $option->getMagentoProduct()->getSku().'-'
                .$this->getHelper('Data')->convertStringToSku($option->getOption());
        }

        if (strlen($sku) >= 80) {
            $sku = 'RANDOM_'.sha1($sku);
        }

        return $sku;
    }

    /**
     * @return int
     */
    public function getQty()
    {
        /** @var $calculator \Ess\M2ePro\Model\Ebay\Listing\Product\QtyCalculator */
        $calculator = $this->modelFactory->getObject('Ebay_Listing_Product_QtyCalculator');
        $calculator->setProduct($this->getListingProduct());
        return $calculator->getVariationValue($this->getParentObject());
    }

    //########################################

    /**
     * @return float|int
     */
    public function getPrice()
    {
        $src = $this->getEbaySellingFormatTemplate()->getFixedPriceSource();

        $vatPercent = null;
        if ($this->getEbaySellingFormatTemplate()->isPriceIncreaseVatPercentEnabled()) {
            $vatPercent = $this->getEbaySellingFormatTemplate()->getVatPercent();
        }

        return $this->getCalculatedPrice(
            $src,
            $vatPercent,
            $this->getEbaySellingFormatTemplate()->getFixedPriceCoefficient()
        );
    }

    // ---------------------------------------

    /**
     * @return float|int
     */
    public function getPriceDiscountStp()
    {
        $src = $this->getEbaySellingFormatTemplate()->getPriceDiscountStpSource();

        $vatPercent = null;
        if ($this->getEbaySellingFormatTemplate()->isPriceIncreaseVatPercentEnabled()) {
            $vatPercent = $this->getEbaySellingFormatTemplate()->getVatPercent();
        }

        return $this->getCalculatedPrice($src, $vatPercent);
    }

    /**
     * @return float|int
     */
    public function getPriceDiscountMap()
    {
        $src = $this->getEbaySellingFormatTemplate()->getPriceDiscountMapSource();

        $vatPercent = null;
        if ($this->getEbaySellingFormatTemplate()->isPriceIncreaseVatPercentEnabled()) {
            $vatPercent = $this->getEbaySellingFormatTemplate()->getVatPercent();
        }

        return $this->getCalculatedPrice($src, $vatPercent);
    }

    // ---------------------------------------

    private function getCalculatedPrice($src, $vatPercent = null, $coefficient = null)
    {
        /** @var $calculator \Ess\M2ePro\Model\Ebay\Listing\Product\PriceCalculator */
        $calculator = $this->modelFactory->getObject('Ebay_Listing_Product_PriceCalculator');
        $calculator->setSource($src)->setProduct($this->getListingProduct());
        $calculator->setVatPercent($vatPercent);
        $calculator->setCoefficient($coefficient);
        $calculator->setPriceVariationMode($this->getEbaySellingFormatTemplate()->getPriceVariationMode());

        return $calculator->getVariationValue($this->getParentObject());
    }

    //########################################

    /**
     * @return bool
     */
    public function hasSales()
    {
        $currentSpecifics = [];

        $options = $this->getOptions(true);
        foreach ($options as $option) {
            /** @var $option \Ess\M2ePro\Model\Listing\Product\Variation\Option */
            $currentSpecifics[$option->getAttribute()] = $option->getOption();
        }

        ksort($currentSpecifics);
        $variationKeys = array_map('trim', array_keys($currentSpecifics));
        $variationValues = array_map('trim', array_values($currentSpecifics));

        $realEbayItemId = $this->getEbayListingProduct()->getEbayItem()->getItemId();

        $tempOrdersItemsCollection = $this->activeRecordFactory->getObject('Ebay_Order_Item')->getCollection();
        $tempOrdersItemsCollection->addFieldToFilter('item_id', $realEbayItemId);

        /** @var \Ess\M2ePro\Model\Ebay\Order\Item[] $ordersItems */
        $ordersItems = $tempOrdersItemsCollection->getItems();

        $findOrderItem = false;

        foreach ($ordersItems as $orderItem) {
            $orderItemVariationOptions = $orderItem->getVariationProductOptions();

            if (empty($orderItemVariationOptions)) {
                continue;
            }

            ksort($orderItemVariationOptions);
            $orderItemVariationKeys = array_map('trim', array_keys($orderItemVariationOptions));
            $orderItemVariationValues = array_map('trim', array_values($orderItemVariationOptions));

            if (count($currentSpecifics) == count($orderItemVariationOptions) &&
                count(array_diff($variationKeys, $orderItemVariationKeys)) <= 0 &&
                count(array_diff($variationValues, $orderItemVariationValues)) <= 0) {
                $findOrderItem = true;
                break;
            }
        }

        return $findOrderItem;
    }

    //########################################
}
