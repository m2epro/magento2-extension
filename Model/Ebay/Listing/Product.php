<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing;

/**
 * @method \Ess\M2ePro\Model\Listing\Product getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product getResource()
 */
class Product extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Ebay\AbstractModel
{
    const INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED = 'channel_status_changed';
    const INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED = 'channel_qty_changed';
    const INSTRUCTION_TYPE_CHANNEL_PRICE_CHANGED = 'channel_price_changed';

    /**
     * @var \Ess\M2ePro\Model\Ebay\Item
     */
    protected $ebayItemModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    private $categoryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Category
     */
    protected $categorySecondaryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    protected $storeCategoryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    protected $storeCategorySecondaryTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Manager[]
     */
    private $templateManagers = [];

    // ---------------------------------------

    /**
     * @var \Ess\M2ePro\Model\Template\SellingFormat
     */
    private $sellingFormatTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Template\Synchronization
     */
    private $synchronizationTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Template\Description
     */
    private $descriptionTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Payment
     */
    private $paymentTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\ReturnPolicy
     */
    private $returnTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Ebay\Template\Shipping
     */
    private $shippingTemplateModel = null;

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

        $this->ebayItemModel                       = null;
        $this->categoryTemplateModel               = null;
        $this->categorySecondaryTemplateModel      = null;
        $this->storeCategoryTemplateModel          = null;
        $this->storeCategorySecondaryTemplateModel = null;
        $this->templateManagers                    = [];
        $this->sellingFormatTemplateModel          = null;
        $this->synchronizationTemplateModel        = null;
        $this->descriptionTemplateModel            = null;
        $this->paymentTemplateModel                = null;
        $this->returnTemplateModel                 = null;
        $this->shippingTemplateModel               = null;

        if ($this->getEbayAccount()->isPickupStoreEnabled()) {
            $this->activeRecordFactory->getObject('Ebay_Listing_Product_PickupStore')
                ->getResource()->processDeletedProduct($this->getParentObject());
        }

        return parent::delete();
    }

    //########################################

    public function afterSaveNewEntity()
    {
        return null;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Ebay\Item
     */
    public function getEbayItem()
    {
        if ($this->ebayItemModel === null) {
            $this->ebayItemModel = $this->activeRecordFactory->getObjectLoaded(
                'Ebay\Item',
                $this->getData('ebay_item_id')
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
        if ($this->categoryTemplateModel === null && $this->isSetCategoryTemplate()) {
            $this->categoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay_Template_Category',
                (int)$this->getTemplateCategoryId()
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
     * @return \Ess\M2ePro\Model\Ebay\Template\Category
     */
    public function getCategorySecondaryTemplate()
    {
        if ($this->categorySecondaryTemplateModel === null && $this->isSetCategorySecondaryTemplate()) {
            $this->categorySecondaryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay_Template_Category',
                (int)$this->getTemplateCategorySecondaryId(),
                null,
                ['template']
            );
        }

        return $this->categorySecondaryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\Category $instance
     */
    public function setCategorySecondaryTemplate(\Ess\M2ePro\Model\Ebay\Template\Category $instance)
    {
        $this->categorySecondaryTemplateModel = $instance;
    }

    //----------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    public function getStoreCategoryTemplate()
    {
        if ($this->storeCategoryTemplateModel === null && $this->isSetStoreCategoryTemplate()) {
            $this->storeCategoryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay_Template_StoreCategory',
                (int)$this->getTemplateStoreCategoryId(),
                null,
                ['template']
            );
        }

        return $this->storeCategoryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance
     */
    public function setStoreCategoryTemplate(\Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance)
    {
        $this->storeCategoryTemplateModel = $instance;
    }

    //----------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory
     */
    public function getStoreCategorySecondaryTemplate()
    {
        if ($this->storeCategorySecondaryTemplateModel === null && $this->isSetStoreCategorySecondaryTemplate()) {
            $this->storeCategorySecondaryTemplateModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Ebay_Template_StoreCategory',
                (int)$this->getTemplateStoreCategorySecondaryId(),
                null,
                ['template']
            );
        }

        return $this->storeCategorySecondaryTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance
     */
    public function setStoreCategorySecondaryTemplate(\Ess\M2ePro\Model\Ebay\Template\StoreCategory $instance)
    {
        $this->storeCategorySecondaryTemplateModel = $instance;
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
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getVariationSpecificsReplacements()
    {
        $specificsReplacements = $this->getParentObject()->getSetting(
            'additional_data',
            'variations_specifics_replacements',
            []
        );

        $replacements = [];
        foreach ($specificsReplacements as $findIt => $replaceBy) {
            $replacements[trim($findIt)] = trim($replaceBy);
        }

        return $replacements;
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
            $manager = $this->modelFactory->getObject('Ebay_Template_Manager')->setOwnerObject($this);
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
        if ($this->sellingFormatTemplateModel === null) {
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
        if ($this->synchronizationTemplateModel === null) {
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
        if ($this->descriptionTemplateModel === null) {
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
        if ($this->paymentTemplateModel === null) {
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
        if ($this->returnTemplateModel === null) {
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
        if ($this->shippingTemplateModel === null) {
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
            return null;
        }

        return $this->getCategoryTemplate()->getSource($this->getMagentoProduct());
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\Category\Source
     */
    public function getCategorySecondaryTemplateSource()
    {
        if (!$this->isSetCategorySecondaryTemplate()) {
            return null;
        }

        return $this->getCategorySecondaryTemplate()->getSource($this->getMagentoProduct());
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory\Source
     */
    public function getStoreCategoryTemplateSource()
    {
        if (!$this->isSetStoreCategoryTemplate()) {
            return null;
        }

        return $this->getStoreCategoryTemplate()->getSource($this->getMagentoProduct());
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Template\StoreCategory\Source
     */
    public function getStoreCategorySecondaryTemplateSource()
    {
        if (!$this->isSetStoreCategorySecondaryTemplate()) {
            return null;
        }

        return $this->getStoreCategorySecondaryTemplate()->getSource($this->getMagentoProduct());
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
    public function getVariations($asObjects = false, array $filters = [], $tryToGetFromStorage = true)
    {
        return $this->getParentObject()->getVariations($asObjects, $filters, $tryToGetFromStorage);
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
        $renderer = $this->modelFactory->getObject('Ebay_Listing_Product_Description_Renderer');
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
        $uuid = str_pad($this->getAccount()->getId(), 2, '0', STR_PAD_LEFT);
        $uuid .= str_pad($this->getListing()->getId(), 4, '0', STR_PAD_LEFT);
        $uuid .= str_pad($this->getId(), 10, '0', STR_PAD_LEFT);

        // max int value is 2147483647 = 0x7FFFFFFF
        // @codingStandardsIgnoreLine
        $randomPart = dechex(call_user_func('mt_rand', 0x000000, 0x7FFFFFFF));
        $uuid .= str_pad($randomPart, 16, '0', STR_PAD_LEFT);

        return strtoupper($uuid);
    }

    // ---------------------------------------

    public function getTemplateCategoryId()
    {
        return $this->getData('template_category_id');
    }

    public function getTemplateCategorySecondaryId()
    {
        return $this->getData('template_category_secondary_id');
    }

    public function getTemplateStoreCategoryId()
    {
        return $this->getData('template_store_category_id');
    }

    public function getTemplateStoreCategorySecondaryId()
    {
        return $this->getData('template_store_category_secondary_id');
    }

    //----------------------------------------

    /**
     * @return bool
     */
    public function isSetCategoryTemplate()
    {
        return $this->getTemplateCategoryId() !== null;
    }

    /**
     * @return bool
     */
    public function isSetCategorySecondaryTemplate()
    {
        return $this->getTemplateCategorySecondaryId() !== null;
    }

    /**
     * @return bool
     */
    public function isSetStoreCategoryTemplate()
    {
        return $this->getTemplateStoreCategoryId() !== null;
    }

    /**
     * @return bool
     */
    public function isSetStoreCategorySecondaryTemplate()
    {
        return $this->getTemplateStoreCategorySecondaryId() !== null;
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

    public function getOnlineSubTitle()
    {
        return $this->getData('online_sub_title');
    }

    public function getOnlineDescription()
    {
        return $this->getData('online_description');
    }

    public function getOnlineImages()
    {
        return $this->getData('online_images');
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

    public function getOnlineMainCategory()
    {
        return $this->getData('online_main_category');
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOnlineCategoriesData()
    {
        return $this->getSettings('online_categories_data');
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOnlineShippingData()
    {
        return $this->getData('online_shipping_data');
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOnlinePaymentData()
    {
        return $this->getData('online_payment_data');
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOnlineReturnData()
    {
        return $this->getData('online_return_data');
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOnlineOtherData()
    {
        return $this->getData('online_other_data');
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
            $sku = 'RANDOM_' . sha1($sku);
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

        if (!$this->isSetCategoryTemplate() || $this->getParentObject()->isGroupedProductModeSet()) {
            $this->setData(__METHOD__, false);
            return false;
        }

        $isVariationEnabled = $this->getHelper('Component_Ebay_Category_Ebay')
            ->isVariationEnabled(
                (int)$this->getCategoryTemplateSource()->getCategoryId(),
                $this->getMarketplace()->getId()
            );

        if ($isVariationEnabled === null) {
            $isVariationEnabled = true;
        }

        $result = $this->getEbayMarketplace()->isMultivariationEnabled() &&
            !$this->getEbaySellingFormatTemplate()->isIgnoreVariationsEnabled() &&
            $isVariationEnabled &&
            $this->isListingTypeFixed() &&
            $this->getMagentoProduct()->isProductWithVariations();

        $this->setData(__METHOD__, $result);

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

        $this->setData(__METHOD__, $result);

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
    public function getStartPrice()
    {
        $price = 0;

        if (!$this->isListingTypeAuction()) {
            return $price;
        }

        $src = $this->getEbaySellingFormatTemplate()->getStartPriceSource();

        $vatPercent = null;
        if ($this->getEbaySellingFormatTemplate()->isPriceIncreaseVatPercentEnabled()) {
            $vatPercent = $this->getEbaySellingFormatTemplate()->getVatPercent();
        }

        return $this->getCalculatedPrice(
            $src,
            $vatPercent,
            $this->getEbaySellingFormatTemplate()->getStartPriceCoefficient()
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

        $vatPercent = null;
        if ($this->getEbaySellingFormatTemplate()->isPriceIncreaseVatPercentEnabled()) {
            $vatPercent = $this->getEbaySellingFormatTemplate()->getVatPercent();
        }

        return $this->getCalculatedPrice(
            $src,
            $vatPercent,
            $this->getEbaySellingFormatTemplate()->getReservePriceCoefficient()
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

        $vatPercent = null;
        if ($this->getEbaySellingFormatTemplate()->isPriceIncreaseVatPercentEnabled()) {
            $vatPercent = $this->getEbaySellingFormatTemplate()->getVatPercent();
        }

        return $this->getCalculatedPrice(
            $src,
            $vatPercent,
            $this->getEbaySellingFormatTemplate()->getBuyItNowPriceCoefficient()
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
        $calculator = $this->modelFactory->getObject('Ebay_Listing_Product_QtyCalculator');
        $calculator->setProduct($this->getParentObject());

        return $calculator->getProductValue();
    }

    //########################################

    public function isOutOfStockControlEnabled()
    {
        if ($this->getOnlineDuration() && !$this->isOnlineDurationGtc()) {
            return false;
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

    public function assignTemplatesToProducts(
        $productsIds,
        $categoryTemplateId = null,
        $categorySecondaryTemplateId = null,
        $storeCategoryTemplateId = null,
        $storeCategorySecondaryTemplateId = null
    ) {
        $this->getResource()->assignTemplatesToProducts(
            $productsIds,
            $categoryTemplateId,
            $categorySecondaryTemplateId,
            $storeCategoryTemplateId,
            $storeCategorySecondaryTemplateId
        );
    }

    //########################################

    public function mapChannelItemProduct()
    {
        $this->getResource()->mapChannelItemProduct($this);
    }

    //########################################
}
