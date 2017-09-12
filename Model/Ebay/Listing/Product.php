<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing;

/**
 * @method \Ess\M2ePro\Model\Listing\Product getParentObject()
 */

class Product extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Ebay\AbstractModel
{
    const TRANSLATION_STATUS_NONE                     = 0;
    const TRANSLATION_STATUS_PENDING                  = 1;
    const TRANSLATION_STATUS_PENDING_PAYMENT_REQUIRED = 2;
    const TRANSLATION_STATUS_IN_PROGRESS              = 3;
    const TRANSLATION_STATUS_TRANSLATED               = 4;

    //########################################

    /**
     * @var \Ess\M2ePro\Model\Ebay\Item
     */
    protected $ebayItemModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    private $categoryTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\OtherCategory
     */
    private $otherCategoryTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Manager[]
     */
    private $templateManagers = array();

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Template\SellingFormat
     */
    private $sellingFormatTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Template\Synchronization
     */
    private $synchronizationTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Template\Description
     */
    private $descriptionTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    private $paymentTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
     */
    private $returnTemplateModel = NULL;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Shipping
     */
    private $shippingTemplateModel = NULL;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product');
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $this->ebayItemModel = NULL;
        $this->categoryTemplateModel = NULL;
        $this->otherCategoryTemplateModel = NULL;
        $this->templateManagers = array();
        $this->sellingFormatTemplateModel = NULL;
        $this->synchronizationTemplateModel = NULL;
        $this->descriptionTemplateModel = NULL;
        $this->paymentTemplateModel = NULL;
        $this->returnTemplateModel = NULL;
        $this->shippingTemplateModel = NULL;

        if ($this->getEbayAccount()->isPickupStoreEnabled()) {
            $this->activeRecordFactory->getObject('Ebay\Listing\Product\PickupStore')
                ->getResource()->processDeletedProduct($this->getParentObject());
        }

        return parent::delete();
    }

    //########################################

    public function afterSaveNewEntity() {}

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Item
     */
    public function getEbayItem()
    {
        if (is_null($this->ebayItemModel)) {
            $this->ebayItemModel = $this->activeRecordFactory->getObjectLoaded(
                'Ebay\Item', $this->getData('ebay_item_id')
            );
        }

        return $this->ebayItemModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Item $instance
     */
    public function setEbayItem(\Ess\M2ePro\Model\Ebay\Item $instance)
    {
         $this->ebayItemModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    public function getCategoryTemplate()
    {
        if (is_null($this->categoryTemplateModel) && $this->isSetCategoryTemplate()) {

            $this->categoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay\Template\Category', (int)$this->getTemplateCategoryId()
            );
        }

        return $this->categoryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Category $instance
     */
    public function setCategoryTemplate(\Ess\M2ePro\Model\Ebay\Template\Category $instance)
    {
         $this->categoryTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\OtherCategory
     */
    public function getOtherCategoryTemplate()
    {
        if (is_null($this->otherCategoryTemplateModel) && $this->isSetOtherCategoryTemplate()) {

            $this->otherCategoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay\Template\OtherCategory', (int)$this->getTemplateOtherCategoryId()
            );
        }

        return $this->otherCategoryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\OtherCategory $instance
     */
    public function setOtherCategoryTemplate(\Ess\M2ePro\Model\Ebay\Template\OtherCategory $instance)
    {
         $this->otherCategoryTemplateModel = $instance;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    public function getMagentoProduct()
    {
        return $this->getParentObject()->getMagentoProduct();
    }

    // ---------------------------------------

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

    //########################################

    /**
     * @param $template
     * @return \Ess\M2ePro\Model\Ebay\Template\Manager
     */
    public function getTemplateManager($template)
    {
        if (!isset($this->templateManagers[$template])) {
            /** @var \Ess\M2ePro\Model\Ebay\Template\Manager $manager */
            $manager = $this->modelFactory->getObject('Ebay\Template\Manager')->setOwnerObject($this);
            $this->templateManagers[$template] = $manager->setTemplate($template);
        }

        return $this->templateManagers[$template];
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        if (is_null($this->sellingFormatTemplateModel)) {
            $template = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT;
            $this->sellingFormatTemplateModel = $this->getTemplateManager($template)->getResultObject();
        }

        return $this->sellingFormatTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\SellingFormat $instance
     */
    public function setSellingFormatTemplate(\Ess\M2ePro\Model\Template\SellingFormat $instance)
    {
         $this->sellingFormatTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Synchronization
     */
    public function getSynchronizationTemplate()
    {
        if (is_null($this->synchronizationTemplateModel)) {
            $template = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION;
            $this->synchronizationTemplateModel = $this->getTemplateManager($template)->getResultObject();
        }

        return $this->synchronizationTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\Synchronization $instance
     */
    public function setSynchronizationTemplate(\Ess\M2ePro\Model\Template\Synchronization $instance)
    {
         $this->synchronizationTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     */
    public function getDescriptionTemplate()
    {
        if (is_null($this->descriptionTemplateModel)) {
            $template = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION;
            $this->descriptionTemplateModel = $this->getTemplateManager($template)->getResultObject();
        }

        return $this->descriptionTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\Description $instance
     */
    public function setDescriptionTemplate(\Ess\M2ePro\Model\Template\Description $instance)
    {
         $this->descriptionTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    public function getPaymentTemplate()
    {
        if (is_null($this->paymentTemplateModel)) {
            $template = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT;
            $this->paymentTemplateModel = $this->getTemplateManager($template)->getResultObject();
        }

        return $this->paymentTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Payment $instance
     */
    public function setPaymentTemplate(\Ess\M2ePro\Model\Ebay\Template\Payment $instance)
    {
         $this->paymentTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
     */
    public function getReturnTemplate()
    {
        if (is_null($this->returnTemplateModel)) {
            $template = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY;
            $this->returnTemplateModel = $this->getTemplateManager($template)->getResultObject();
        }

        return $this->returnTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy $instance
     */
    public function setReturnTemplate(\Ess\M2ePro\Model\Ebay\Template\ReturnPolicy $instance)
    {
         $this->returnTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping
     */
    public function getShippingTemplate()
    {
        if (is_null($this->shippingTemplateModel)) {
            $template = \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING;
            $this->shippingTemplateModel = $this->getTemplateManager($template)->getResultObject();
        }

        return $this->shippingTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Shipping $instance
     */
    public function setShippingTemplate(\Ess\M2ePro\Model\Ebay\Template\Shipping $instance)
    {
         $this->shippingTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\SellingFormat
     */
    public function getEbaySellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Synchronization
     */
    public function getEbaySynchronizationTemplate()
    {
        return $this->getSynchronizationTemplate()->getChildObject();
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
     * @return \Ess\M2ePro\Model\Ebay\Template\Category\Source
     */
    public function getCategoryTemplateSource()
    {
        if (!$this->isSetCategoryTemplate()) {
            return NULL;
        }

        return $this->getCategoryTemplate()->getSource($this->getMagentoProduct());
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\OtherCategory\Source
     */
    public function getOtherCategoryTemplateSource()
    {
        if (!$this->isSetOtherCategoryTemplate()) {
            return NULL;
        }

        return $this->getOtherCategoryTemplate()->getSource($this->getMagentoProduct());
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\SellingFormat\Source
     */
    public function getSellingFormatTemplateSource()
    {
        return $this->getEbaySellingFormatTemplate()->getSource($this->getMagentoProduct());
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Description\Source
     */
    public function getDescriptionTemplateSource()
    {
        return $this->getEbayDescriptionTemplate()->getSource($this->getMagentoProduct());
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Shipping\Source
     */
    public function getShippingTemplateSource()
    {
        return $this->getShippingTemplate()->getSource($this->getMagentoProduct());
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @param bool $tryToGetFromStorage
     * @return array
     */
    public function getVariations($asObjects = false, array $filters = array(), $tryToGetFromStorage = true)
    {
        return $this->getParentObject()->getVariations($asObjects,$filters,$tryToGetFromStorage);
    }

    //########################################

    public function updateVariationsStatus()
    {
        foreach ($this->getVariations(true) as $variation) {
            $variation->getChildObject()->setStatus($this->getParentObject()->getStatus());
        }
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Listing\Product\Description\Renderer
    **/
    public function getDescriptionRenderer()
    {
        $renderer = $this->modelFactory->getObject('Ebay\Listing\Product\Description\Renderer');
        $renderer->setListingProduct($this);

        return $renderer;
    }

    //########################################

    /**
     * @return float
     */
    public function getEbayItemIdReal()
    {
        return $this->getEbayItem()->getItemId();
    }

    //########################################

    /**
     * @return int
     */
    public function getEbayItemId()
    {
        return (int)$this->getData('ebay_item_id');
    }

    public function getItemUUID()
    {
        return $this->getData('item_uuid');
    }

    public function generateItemUUID()
    {
        $uuid  = str_pad($this->getAccount()->getId(), 2, '0', STR_PAD_LEFT);
        $uuid .= str_pad($this->getListing()->getId(), 4, '0', STR_PAD_LEFT);
        $uuid .= str_pad($this->getId(), 10, '0', STR_PAD_LEFT);

        // max int value is 2147483647 = 0x7FFFFFFF
        $randomPart = dechex(mt_rand(0x000000, 0x7FFFFFFF));
        $uuid .= str_pad($randomPart, 16, '0', STR_PAD_LEFT);

        return strtoupper($uuid);
    }

    // ---------------------------------------

    public function getTemplateCategoryId()
    {
        return $this->getData('template_category_id');
    }

    public function getTemplateOtherCategoryId()
    {
        return $this->getData('template_other_category_id');
    }

    /**
     * @return bool
     */
    public function isSetCategoryTemplate()
    {
        return !is_null($this->getTemplateCategoryId());
    }

    /**
     * @return bool
     */
    public function isSetOtherCategoryTemplate()
    {
        return !is_null($this->getTemplateOtherCategoryId());
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isOnlineVariation()
    {
        return (bool)$this->getData("online_is_variation");
    }

    /**
     * @return bool
     */
    public function isOnlineAuctionType()
    {
        return (bool)$this->getData("online_is_auction_type");
    }

    // ---------------------------------------

    public function getOnlineSku()
    {
        return $this->getData('online_sku');
    }

    public function getOnlineTitle()
    {
        return $this->getData('online_title');
    }

    public function getOnlineDuration()
    {
        return $this->getData('online_duration');
    }

    // ---------------------------------------

    /**
     * @return float
     */
    public function getOnlineCurrentPrice()
    {
        return (float)$this->getData('online_current_price');
    }

    /**
     * @return float
     */
    public function getOnlineStartPrice()
    {
        return (float)$this->getData('online_start_price');
    }

    /**
     * @return float
     */
    public function getOnlineReservePrice()
    {
        return (float)$this->getData('online_reserve_price');
    }

    /**
     * @return float
     */
    public function getOnlineBuyItNowPrice()
    {
        return (float)$this->getData('online_buyitnow_price');
    }

    // ---------------------------------------

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

    /**
     * @return int
     */
    public function getOnlineBids()
    {
        return (int)$this->getData('online_bids');
    }

    public function getOnlineCategory()
    {
        return $this->getData('online_category');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getTranslationStatus()
    {
        return (int)$this->getData('translation_status');
    }

    /**
     * @return bool
     */
    public function isTranslationStatusNone()
    {
        return $this->getTranslationStatus() == self::TRANSLATION_STATUS_NONE;
    }

    /**
     * @return bool
     */
    public function isTranslationStatusPending()
    {
        return $this->getTranslationStatus() == self::TRANSLATION_STATUS_PENDING;
    }

    /**
     * @return bool
     */
    public function isTranslationStatusPendingPaymentRequired()
    {
        return $this->getTranslationStatus() == self::TRANSLATION_STATUS_PENDING_PAYMENT_REQUIRED;
    }

    /**
     * @return bool
     */
    public function isTranslationStatusInProgress()
    {
        return $this->getTranslationStatus() == self::TRANSLATION_STATUS_IN_PROGRESS;
    }

    /**
     * @return bool
     */
    public function isTranslationStatusTranslated()
    {
        return $this->getTranslationStatus() == self::TRANSLATION_STATUS_TRANSLATED;
    }

    /**
     * @return bool
     */
    public function isTranslatable()
    {
        return $this->isTranslationStatusPending() || $this->isTranslationStatusPendingPaymentRequired();
    }

    public function getTranslationService()
    {
        return $this->getData('translation_service');
    }

    public function getTranslatedDate()
    {
        return $this->getData('translated_date');
    }

    // ---------------------------------------

    public function getStartDate()
    {
        return $this->getData('start_date');
    }

    public function getEndDate()
    {
        return $this->getData('end_date');
    }

    //########################################

    public function getSku()
    {
        $sku = $this->getMagentoProduct()->getSku();

        if (strlen($sku) >= 50) {
            $sku = 'RANDOM_'.sha1($sku);
        }

        return $sku;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isListingTypeFixed()
    {
        return $this->getSellingFormatTemplateSource()->getListingType() ==
              \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_FIXED;
    }

    /**
     * @return bool
     */
    public function isListingTypeAuction()
    {
        return $this->getSellingFormatTemplateSource()->getListingType() ==
              \Ess\M2ePro\Model\Ebay\Template\SellingFormat::LISTING_TYPE_AUCTION;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isVariationMode()
    {
        if ($this->hasData(__METHOD__)) {
            return $this->getData(__METHOD__);
        }

        if (!$this->isSetCategoryTemplate()) {
            $this->setData(__METHOD__,false);
            return false;
        }

        $isVariationEnabled = $this->getHelper('Component\Ebay\Category\Ebay')
                                                ->isVariationEnabled(
                                                    (int)$this->getCategoryTemplateSource()->getMainCategory(),
                                                    $this->getMarketplace()->getId()
                                                );

        if (is_null($isVariationEnabled)) {
            $isVariationEnabled = true;
        }

        $result = $this->getEbayMarketplace()->isMultivariationEnabled() &&
                  !$this->getEbaySellingFormatTemplate()->isIgnoreVariationsEnabled() &&
                  $isVariationEnabled &&
                  $this->isListingTypeFixed() &&
                  $this->getMagentoProduct()->isProductWithVariations();

        $this->setData(__METHOD__,$result);

        return $result;
    }

    /**
     * @return bool
     */
    public function isVariationsReady()
    {
        if ($this->hasData(__METHOD__)) {
            return $this->getData(__METHOD__);
        }

        $result = $this->isVariationMode() && count($this->getVariations()) > 0;

        $this->setData(__METHOD__,$result);

        return $result;
    }

    //########################################

    /**
     * @return bool
     */
    public function isPriceDiscountStp()
    {
        return $this->getEbayMarketplace()->isStpEnabled() &&
               !$this->getEbaySellingFormatTemplate()->isPriceDiscountStpModeNone();
    }

    /**
     * @return bool
     */
    public function isPriceDiscountMap()
    {
        return $this->getEbayMarketplace()->isMapEnabled() &&
               !$this->getEbaySellingFormatTemplate()->isPriceDiscountMapModeNone();
    }

    //########################################

    /**
     * @return float|int
     */
    public function getFixedPrice()
    {
        $src = $this->getEbaySellingFormatTemplate()->getFixedPriceSource();

        $vatPercent = NULL;
        if ($this->getEbaySellingFormatTemplate()->isPriceIncreaseVatPercentEnabled()) {
            $vatPercent = $this->getEbaySellingFormatTemplate()->getVatPercent();
        }

        return $this->getCalculatedPrice(
            $src, $vatPercent, $this->getEbaySellingFormatTemplate()->getFixedPriceCoefficient()
        );
    }

    // ---------------------------------------

    /**
     * @return float|int
     */
    public function getStartPrice()
    {
        $price = 0;

        if (!$this->isListingTypeAuction()) {
            return $price;
        }

        $src = $this->getEbaySellingFormatTemplate()->getStartPriceSource();

        $vatPercent = NULL;
        if ($this->getEbaySellingFormatTemplate()->isPriceIncreaseVatPercentEnabled()) {
            $vatPercent = $this->getEbaySellingFormatTemplate()->getVatPercent();
        }

        return $this->getCalculatedPrice(
            $src, $vatPercent, $this->getEbaySellingFormatTemplate()->getStartPriceCoefficient()
        );
    }

    /**
     * @return float|int
     */
    public function getReservePrice()
    {
        $price = 0;

        if (!$this->isListingTypeAuction()) {
            return $price;
        }

        $src = $this->getEbaySellingFormatTemplate()->getReservePriceSource();

        $vatPercent = NULL;
        if ($this->getEbaySellingFormatTemplate()->isPriceIncreaseVatPercentEnabled()) {
            $vatPercent = $this->getEbaySellingFormatTemplate()->getVatPercent();
        }

        return $this->getCalculatedPrice(
            $src, $vatPercent, $this->getEbaySellingFormatTemplate()->getReservePriceCoefficient()
        );
    }

    /**
     * @return float|int
     */
    public function getBuyItNowPrice()
    {
        $price = 0;

        if (!$this->isListingTypeAuction()) {
            return $price;
        }

        $src = $this->getEbaySellingFormatTemplate()->getBuyItNowPriceSource();

        $vatPercent = NULL;
        if ($this->getEbaySellingFormatTemplate()->isPriceIncreaseVatPercentEnabled()) {
            $vatPercent = $this->getEbaySellingFormatTemplate()->getVatPercent();
        }

        return $this->getCalculatedPrice(
            $src, $vatPercent, $this->getEbaySellingFormatTemplate()->getBuyItNowPriceCoefficient()
        );
    }

    // ---------------------------------------

    /**
     * @return float|int
     */
    public function getPriceDiscountStp()
    {
        $src = $this->getEbaySellingFormatTemplate()->getPriceDiscountStpSource();

        $vatPercent = NULL;
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

        $vatPercent = NULL;
        if ($this->getEbaySellingFormatTemplate()->isPriceIncreaseVatPercentEnabled()) {
            $vatPercent = $this->getEbaySellingFormatTemplate()->getVatPercent();
        }

        return $this->getCalculatedPrice($src, $vatPercent);
    }

    // ---------------------------------------

    private function getCalculatedPrice($src, $vatPercent = NULL, $coefficient = NULL)
    {
        /** @var $calculator \Ess\M2ePro\Model\Ebay\Listing\Product\PriceCalculator */
        $calculator = $this->modelFactory->getObject('Ebay\Listing\Product\PriceCalculator');
        $calculator->setSource($src)->setProduct($this->getParentObject());
        $calculator->setVatPercent($vatPercent);
        $calculator->setCoefficient($coefficient);

        return $calculator->getProductValue();
    }

    //########################################

    /**
     * @return int
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getQty()
    {
        if ($this->isListingTypeAuction()) {
            return 1;
        }

        if ($this->isVariationsReady()) {

            $qty = 0;

            foreach ($this->getVariations(true) as $variation) {
                /** @var $variation \Ess\M2ePro\Model\Listing\Product\Variation */
                $qty += $variation->getChildObject()->getQty();
            }

            return $qty;
        }

        /** @var $calculator \Ess\M2ePro\Model\Ebay\Listing\Product\QtyCalculator */
        $calculator = $this->modelFactory->getObject('Ebay\Listing\Product\QtyCalculator');
        $calculator->setProduct($this->getParentObject());

        return $calculator->getProductValue();
    }

    //########################################

    public function getOutOfStockControl($returnRealValue = false)
    {
        $additionalData = $this->getParentObject()->getAdditionalData();

        if (isset($additionalData['out_of_stock_control'])) {
            return (bool)$additionalData['out_of_stock_control'];
        }

        return $returnRealValue ? NULL : false;
    }

    public function isOutOfStockControlEnabled()
    {
        if ($this->getOnlineDuration() && !$this->isOnlineDurationGtc()) {
            return false;
        }

        if ($this->getOutOfStockControl()) {
            return true;
        }

        if ($this->getEbayAccount()->getOutOfStockControl()) {
            return true;
        }

        return false;
    }

    //########################################

    public function isOnlineDurationGtc()
    {
        return $this->getOnlineDuration() == \Ess\M2ePro\Helper\Component\Ebay::LISTING_DURATION_GTC;
    }

    //########################################

    /**
     * @return float|int
     */
    public function getBestOfferAcceptPrice()
    {
        if (!$this->isListingTypeFixed()) {
            return 0;
        }

        if (!$this->getEbaySellingFormatTemplate()->isBestOfferEnabled()) {
            return 0;
        }

        if ($this->getEbaySellingFormatTemplate()->isBestOfferAcceptModeNo()) {
            return 0;
        }

        $src = $this->getEbaySellingFormatTemplate()->getBestOfferAcceptSource();

        $price = 0;
        switch ($src['mode']) {
            case \Ess\M2ePro\Model\Ebay\Template\SellingFormat::BEST_OFFER_ACCEPT_MODE_PERCENTAGE:
                $price = $this->getFixedPrice() * (float)$src['value'] / 100;
                break;

            case \Ess\M2ePro\Model\Ebay\Template\SellingFormat::BEST_OFFER_ACCEPT_MODE_ATTRIBUTE:
                $price = (float)$this->getHelper('Magento\Attribute')
                    ->convertAttributeTypePriceFromStoreToMarketplace(
                        $this->getMagentoProduct(),
                        $src['attribute'],
                        $this->getEbayListing()->getEbayMarketplace()->getCurrency(),
                        $this->getListing()->getStoreId()
                    );
                break;
        }

        return round($price, 2);
    }

    /**
     * @return float|int
     */
    public function getBestOfferRejectPrice()
    {
        if (!$this->isListingTypeFixed()) {
            return 0;
        }

        if (!$this->getEbaySellingFormatTemplate()->isBestOfferEnabled()) {
            return 0;
        }

        if ($this->getEbaySellingFormatTemplate()->isBestOfferRejectModeNo()) {
            return 0;
        }

        $src = $this->getEbaySellingFormatTemplate()->getBestOfferRejectSource();

        $price = 0;
        switch ($src['mode']) {
            case \Ess\M2ePro\Model\Ebay\Template\SellingFormat::BEST_OFFER_REJECT_MODE_PERCENTAGE:
                $price = $this->getFixedPrice() * (float)$src['value'] / 100;
                break;

            case \Ess\M2ePro\Model\Ebay\Template\SellingFormat::BEST_OFFER_REJECT_MODE_ATTRIBUTE:
                $price = (float)$this->getHelper('Magento\Attribute')
                    ->convertAttributeTypePriceFromStoreToMarketplace(
                        $this->getMagentoProduct(),
                        $src['attribute'],
                        $this->getEbayListing()->getEbayMarketplace()->getCurrency(),
                        $this->getListing()->getStoreId()
                    );
                break;
        }

        return round($price, 2);
    }

    //########################################

    public function listAction(array $params = array())
    {
        return $this->processDispatcher(\Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $params);
    }

    public function relistAction(array $params = array())
    {
        return $this->processDispatcher(\Ess\M2ePro\Model\Listing\Product::ACTION_RELIST, $params);
    }

    public function reviseAction(array $params = array())
    {
        return $this->processDispatcher(\Ess\M2ePro\Model\Listing\Product::ACTION_REVISE, $params);
    }

    public function stopAction(array $params = array())
    {
        return $this->processDispatcher(\Ess\M2ePro\Model\Listing\Product::ACTION_STOP, $params);
    }

    // ---------------------------------------

    protected function processDispatcher($action, array $params = array())
    {
        return $this->modelFactory->getObject('Ebay\Connector\Item\Dispatcher')
            ->process($action, $this->getId(), $params);
    }

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getTrackingAttributes()
    {
        $attributes = $this->getListing()->getTrackingAttributes();

        $trackingAttributesTemplates = $this->modelFactory->getObject('Ebay\Template\Manager')
            ->getTrackingAttributesTemplates();

        foreach ($trackingAttributesTemplates as $template) {
            $templateManager = $this->getTemplateManager($template);
            $resultObjectTemp = $templateManager->getResultObject();
            if ($resultObjectTemp) {
                $attributes = array_merge($attributes,$resultObjectTemp->getTrackingAttributes());
            }
        }

        if ($this->isSetCategoryTemplate()) {
            $attributes = array_merge($attributes,$this->getCategoryTemplate()->getTrackingAttributes());
        }

        if ($this->getEbayAccount()->isPickupStoreEnabled()) {
            $listingProductPickupStoreCollection = $this->activeRecordFactory
                ->getObject('Ebay\Listing\Product\PickupStore')
                ->getCollection()
                ->addFieldToFilter('listing_product_id', $this->getId());

            foreach ($listingProductPickupStoreCollection as $listingProductPickupStore) {
                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\PickupStore $listingProductPickupStore */
                $tempAttributes = $listingProductPickupStore->getAccountPickupStore()->getTrackingAttributes();
                $attributes = array_merge($attributes, $tempAttributes);
            }
        }

        return array_unique($attributes);
    }

    public function setSynchStatusNeed($newData, $oldData)
    {
        $templateManager = $this->modelFactory->getObject('Ebay\Template\Manager');

        $newTemplates = $templateManager->getTemplatesFromData($newData);
        $oldTemplates = $templateManager->getTemplatesFromData($oldData);

        $listingProductData = array_merge(
            $this->getParentObject()->getData(),
            $this->getData()
        );

        foreach ($templateManager->getAllTemplates() as $template) {

            $templateManager->setTemplate($template);

            $templateManager->getTemplateModel(true)->getResource()->setSynchStatusNeed(
                $newTemplates[$template]->getDataSnapshot(),
                $oldTemplates[$template]->getDataSnapshot(),
                array($listingProductData)
            );
        }
        $this->getResource()->setSynchStatusNeedByCategoryTemplate($newData,$oldData,$listingProductData);
        $this->getResource()->setSynchStatusNeedByOtherCategoryTemplate($newData,$oldData,$listingProductData);
    }

    // ---------------------------------------

    public function clearParentIndexer()
    {
        $manager = $this->modelFactory->getObject('Indexer\Listing\Product\VariationParent\Manager', [
            'listing' => $this->getListing()
        ]);
        $manager->markInvalidated();
    }

    //########################################

    public function beforeSave()
    {
        if ($this->isObjectCreatingState()) {
            $this->setData('item_uuid', $this->generateItemUUID());
        }

        return parent::beforeSave();
    }

    public function afterSave()
    {
        if ($this->isObjectCreatingState()) {

            $this->clearParentIndexer();
        } else {

            /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Indexer\Listing\Product\VariationParent $resource */
            $resource = $this->activeRecordFactory->getObject(
                'Ebay\Indexer\Listing\Product\VariationParent'
            )->getResource();

            foreach ($resource->getTrackedFields() as $fieldName) {
                if ($this->getData($fieldName) != $this->getOrigData($fieldName)) {

                    $this->clearParentIndexer();
                    break;
                }
            }
        }

        return parent::afterSave();
    }

    public function beforeDelete()
    {
        $this->clearParentIndexer();

        parent::beforeDelete();
    }

    //########################################
}