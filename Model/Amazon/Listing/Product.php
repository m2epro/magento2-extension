<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing;

/**
 * @method \Ess\M2ePro\Model\Listing\Product getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product getResource()
 */
class Product extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    public const INSTRUCTION_TYPE_CHANNEL_STATUS_CHANGED = 'channel_status_changed';
    public const INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED = 'channel_qty_changed';
    public const INSTRUCTION_TYPE_CHANNEL_REGULAR_PRICE_CHANGED = 'channel_regular_price_changed';

    public const IS_AFN_CHANNEL_NO = 0;
    public const IS_AFN_CHANNEL_YES = 1;

    public const IS_REPRICING_NO = 0;
    public const IS_REPRICING_YES = 1;

    public const VARIATION_PARENT_IS_AFN_STATE_ALL_NO = 0;
    public const VARIATION_PARENT_IS_AFN_STATE_PARTIAL = 1;
    public const VARIATION_PARENT_IS_AFN_STATE_ALL_YES = 2;

    public const VARIATION_PARENT_IS_REPRICING_STATE_ALL_NO = 0;
    public const VARIATION_PARENT_IS_REPRICING_STATE_PARTIAL = 1;
    public const VARIATION_PARENT_IS_REPRICING_STATE_ALL_YES = 2;

    public const IS_ISBN_GENERAL_ID_NO = 0;
    public const IS_ISBN_GENERAL_ID_YES = 1;

    public const IS_GENERAL_ID_OWNER_NO = 0;
    public const IS_GENERAL_ID_OWNER_YES = 1;

    public const SEARCH_SETTINGS_STATUS_IN_PROGRESS = 1;
    public const SEARCH_SETTINGS_STATUS_NOT_FOUND = 2;
    public const SEARCH_SETTINGS_STATUS_ACTION_REQUIRED = 3;
    public const SEARCH_SETTINGS_IDENTIFIER_INVALID = 4;

    public const GENERAL_ID_STATE_SET = 0;
    public const GENERAL_ID_STATE_NOT_SET = 1;
    public const GENERAL_ID_STATE_ACTION_REQUIRED = 2;
    public const GENERAL_ID_STATE_READY_FOR_NEW_ASIN = 3;

    public const BUSINESS_DISCOUNTS_MAX_RULES_COUNT_ALLOWED = 5;

    /** @var \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory */
    private $productTypeFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculatorFactory */
    private $amazonPriceCalculatorFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Identifiers\Factory */
    private $identifiersFactory;
    /** @var \Ess\M2ePro\Model\Amazon\Search\Dispatcher */
    private $searchDispatcher;
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Configuration */
    private $configuration;
    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager|null */
    private $variationManager = null;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing|null */
    private $repricingModel = null;

    /**
     * @param \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculatorFactory $amazonPriceCalculatorFactory
     * @param \Ess\M2ePro\Model\Amazon\Listing\Product\Identifiers\Factory $identifiesFactory
     * @param \Ess\M2ePro\Model\Amazon\Search\Dispatcher $searchDispatcher
     * @param \Ess\M2ePro\Helper\Component\Amazon\Configuration $configuration
     * @param \Ess\M2ePro\Helper\Data $helperData
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Model\Amazon\Template\ProductTypeFactory $productTypeFactory,
        \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculatorFactory $amazonPriceCalculatorFactory,
        \Ess\M2ePro\Model\Amazon\Listing\Product\Identifiers\Factory $identifiesFactory,
        \Ess\M2ePro\Model\Amazon\Search\Dispatcher $searchDispatcher,
        \Ess\M2ePro\Helper\Component\Amazon\Configuration $configuration,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->productTypeFactory = $productTypeFactory;
        $this->amazonPriceCalculatorFactory = $amazonPriceCalculatorFactory;
        $this->identifiersFactory = $identifiesFactory;
        $this->searchDispatcher = $searchDispatcher;
        $this->configuration = $configuration;
        $this->helperData = $helperData;
        parent::__construct(
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product::class);
    }

    public function getListingProductId(): int
    {
        return (int)$this->getData('listing_product_id');
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            foreach ($this->getVariationManager()->getTypeModel()->getChildListingsProducts() as $child) {
                /** @var \Ess\M2ePro\Model\Listing\Product $child */
                if ($child->getStatus() == \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            foreach ($this->getVariationManager()->getTypeModel()->getChildListingsProducts() as $child) {
                /** @var \Ess\M2ePro\Model\Listing\Product $child */
                $child->delete();
            }
        }

        if ($this->isRepricingUsed()) {
            $this->getRepricing()->delete();
        }

        $this->variationManager = null;

        return parent::delete();
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isVariationMode()
    {
        if ($this->hasData(__METHOD__)) {
            return $this->getData(__METHOD__);
        }

        $result = $this->getMagentoProduct()->isProductWithVariations();

        if ($this->getParentObject()->isGroupedProductModeSet()) {
            $result = false;
        }

        $this->setData(__METHOD__, $result);

        return $result;
    }

    /**
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function afterSaveNewEntity()
    {
        $this->searchDispatcher->runSettings([$this->getParentObject()]);

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager $variationManager */
        $variationManager = $this->getVariationManager();
        if ($variationManager->isVariationProduct() || !$this->isVariationMode()) {
            return null;
        }

        $this->setData('is_variation_product', 1);

        $variationManager->setRelationParentType();
        $variationManager->getTypeModel()->resetProductAttributes(false);
        $variationManager->getTypeModel()->getProcessor()->process();
    }

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        return $this->getParentObject()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account
     */
    public function getAmazonAccount()
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
     * @return \Ess\M2ePro\Model\Amazon\Marketplace
     */
    public function getAmazonMarketplace()
    {
        return $this->getMarketplace()->getChildObject();
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
     * @return \Ess\M2ePro\Model\Amazon\Listing
     */
    public function getAmazonListing()
    {
        return $this->getListing()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Source
     */
    public function getListingSource()
    {
        return $this->getAmazonListing()->getSource($this->getActualMagentoProduct());
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Identifiers
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getIdentifiers(): \Ess\M2ePro\Model\Amazon\Listing\Product\Identifiers
    {
        return $this->identifiersFactory->create($this->getActualMagentoProduct());
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        return $this->getAmazonListing()->getSellingFormatTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\SellingFormat
     */
    public function getAmazonSellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Synchronization
     */
    public function getSynchronizationTemplate()
    {
        return $this->getAmazonListing()->getSynchronizationTemplate();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Synchronization
     */
    public function getAmazonSynchronizationTemplate()
    {
        return $this->getSynchronizationTemplate()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isExistShippingTemplate()
    {
        return $this->getTemplateShippingId() > 0;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Shipping | null
     */
    public function getShippingTemplate()
    {
        if (!$this->isExistShippingTemplate()) {
            return null;
        }

        return $this->activeRecordFactory->getCachedObjectLoaded(
            'Amazon_Template_Shipping',
            $this->getTemplateShippingId()
        );
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Shipping\Source
     */
    public function getShippingTemplateSource()
    {
        if (!$this->isExistShippingTemplate()) {
            return null;
        }

        return $this->getShippingTemplate()->getSource($this->getActualMagentoProduct());
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isExistProductTaxCodeTemplate()
    {
        return $this->getTemplateProductTaxCodeId() > 0;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode | null
     */
    public function getProductTaxCodeTemplate()
    {
        if (!$this->isExistProductTaxCodeTemplate()) {
            return null;
        }

        return $this->activeRecordFactory->getCachedObjectLoaded(
            'Amazon_Template_ProductTaxCode',
            $this->getTemplateProductTaxCodeId()
        );
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\ProductTaxCode\Source
     */
    public function getProductTaxCodeTemplateSource()
    {
        if (!$this->isExistProductTaxCodeTemplate()) {
            return null;
        }

        return $this->getProductTaxCodeTemplate()->getSource($this->getActualMagentoProduct());
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isExistsProductTypeTemplate(): bool
    {
        return $this->getTemplateProductTypeId() > 0;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\ProductType|null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProductTypeTemplate(): ?\Ess\M2ePro\Model\Amazon\Template\ProductType
    {
        if (!$this->isExistsProductTypeTemplate()) {
            return null;
        }

        $productType = $this->productTypeFactory->create();
        $productType->load($this->getTemplateProductTypeId());

        return $productType;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    public function getMagentoProduct()
    {
        return $this->getParentObject()->getMagentoProduct();
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function getActualMagentoProduct()
    {
        if (
            !$this->getVariationManager()->isPhysicalUnit()
            || !$this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            return $this->getMagentoProduct();
        }

        if (
            $this->getMagentoProduct()->isConfigurableType()
            || $this->getMagentoProduct()->isGroupedType()
        ) {
            $variations = $this->getVariations(true);
            if (empty($variations)) {
                throw new \Ess\M2ePro\Model\Exception\Logic(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId(),
                    ]
                );
            }
            $variation = reset($variations);
            $options = $variation->getOptions(true);
            $option = reset($options);

            return $option->getMagentoProduct();
        }

        return $this->getMagentoProduct();
    }

    /**
     * @param \Ess\M2ePro\Model\Magento\Product\Cache $instance
     *
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function prepareMagentoProduct(\Ess\M2ePro\Model\Magento\Product\Cache $instance)
    {
        if (!$this->getVariationManager()->isRelationMode()) {
            return $instance;
        }

        if ($this->getVariationManager()->isRelationParentType()) {
            $parentTypeModel = $this->getVariationManager()->getTypeModel();
        } else {
            $parentAmazonListingProduct = $this->getVariationManager()->getTypeModel()->getAmazonParentListingProduct();
            $parentTypeModel = $parentAmazonListingProduct->getVariationManager()->getTypeModel();
        }

        $instance->setVariationVirtualAttributes($parentTypeModel->getVirtualProductAttributes());
        $instance->setVariationFilterAttributes($parentTypeModel->getVirtualChannelAttributes());

        return $instance;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Item
     */
    public function getAmazonItem()
    {
        return $this->activeRecordFactory->getObject('Amazon\Item')->getCollection()
                                         ->addFieldToFilter('account_id', $this->getListing()->getAccountId())
                                         ->addFieldToFilter('marketplace_id', $this->getListing()->getMarketplaceId())
                                         ->addFieldToFilter('sku', $this->getSku())
                                         ->setOrder('create_date', \Magento\Framework\Data\Collection::SORT_ORDER_DESC)
                                         ->getFirstItem();
    }

    public function getVariationManager()
    {
        if ($this->variationManager === null) {
            $this->variationManager = $this->modelFactory->getObject('Amazon_Listing_Product_Variation_Manager');
            $this->variationManager->setListingProduct($this->getParentObject());
        }

        return $this->variationManager;
    }

    /**
     * @param bool $asObjects
     * @param array $filters
     * @param bool $tryToGetFromStorage
     *
     * @return array
     */
    public function getVariations($asObjects = false, array $filters = [], $tryToGetFromStorage = true)
    {
        return $this->getParentObject()->getVariations($asObjects, $filters, $tryToGetFromStorage);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Amazon\Listing\Product\Repricing
     */
    public function getRepricing()
    {
        if ($this->repricingModel === null) {
            $this->repricingModel = $this->activeRecordFactory->getObjectLoaded(
                'Amazon_Listing_Product_Repricing',
                $this->getId(),
                null,
                false
            );
        }

        return $this->repricingModel;
    }

    /**
     * @return bool
     */
    public function isRepricingUsed()
    {
        return $this->isRepricing() && $this->getRepricing() !== null;
    }

    /**
     * @return bool
     */
    public function isRepricingManaged()
    {
        return $this->isRepricingUsed() &&
            !$this->getRepricing()->isOnlineDisabled() && !$this->getRepricing()->isOnlineInactive();
    }

    //########################################

    /**
     * @return int
     */
    public function getTemplateShippingId()
    {
        return (int)($this->getData('template_shipping_id'));
    }

    /**
     * @return int
     */
    public function getTemplateProductTaxCodeId()
    {
        return (int)($this->getData('template_product_tax_code_id'));
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getSku()
    {
        return $this->getData('sku');
    }

    /**
     * @return int
     */
    public function getTemplateProductTypeId(): int
    {
        return (int)$this->getData('template_product_type_id');
    }

    /**
     * @param int|string|null $productType - null as default value
     *
     * @return void
     */
    public function setTemplateProductTypeId($productType)
    {
        $this->setData('template_product_type_id', $productType);
    }

    /**
     * @return string
     */
    public function getGeneralId()
    {
        return $this->getData('general_id');
    }

    // ---------------------------------------

    /**
     * @return float|null
     */
    public function getOnlineRegularPrice()
    {
        return $this->getData('online_regular_price');
    }

    public function getOnlineRegularSalePrice()
    {
        return $this->getData('online_regular_sale_price');
    }

    public function getOnlineRegularSalePriceStartDate()
    {
        return $this->getData('online_regular_sale_price_start_date');
    }

    public function getOnlineRegularSalePriceEndDate()
    {
        return $this->getData('online_regular_sale_price_end_date');
    }

    // ---------------------------------------

    /**
     * @return float|null
     */
    public function getOnlineBusinessPrice()
    {
        return (float)$this->getData('online_business_price');
    }

    public function getOnlineBusinessDiscounts()
    {
        return $this->getSettings('online_business_discounts');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getOnlineQty()
    {
        return (int)$this->getData('online_qty');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isRepricing()
    {
        return (int)$this->getData('is_repricing') == self::IS_REPRICING_YES;
    }

    /**
     * @return bool
     */
    public function isAfnChannel()
    {
        return (int)$this->getData('is_afn_channel') == self::IS_AFN_CHANNEL_YES;
    }

    /**
     * @return bool
     */
    public function isIsbnGeneralId()
    {
        return (int)$this->getData('is_isbn_general_id') == self::IS_ISBN_GENERAL_ID_YES;
    }

    /**
     * @return bool
     */
    public function isGeneralIdOwner()
    {
        return (int)$this->getData('is_general_id_owner') == self::IS_GENERAL_ID_OWNER_YES;
    }

    // ---------------------------------------

    public function getVariationParentAfnState()
    {
        return $this->getData('variation_parent_afn_state');
    }

    public function isVariationParentAfnStateNo()
    {
        return (int)$this->getVariationParentAfnState() == self::VARIATION_PARENT_IS_AFN_STATE_ALL_NO;
    }

    public function isVariationParentAfnStatePartial()
    {
        return (int)$this->getVariationParentAfnState() == self::VARIATION_PARENT_IS_AFN_STATE_PARTIAL;
    }

    public function isVariationParentAfnStateYes()
    {
        return (int)$this->getVariationParentAfnState() == self::VARIATION_PARENT_IS_AFN_STATE_ALL_YES;
    }

    // ---------------------------------------

    public function getVariationParentRepricingState()
    {
        return $this->getData('variation_parent_repricing_state');
    }

    public function isVariationParentRepricingStateNo()
    {
        return (int)$this->getVariationParentRepricingState() == self::VARIATION_PARENT_IS_REPRICING_STATE_ALL_NO;
    }

    public function isVariationParentRepricingStatePartial()
    {
        return (int)$this->getVariationParentRepricingState() == self::VARIATION_PARENT_IS_REPRICING_STATE_PARTIAL;
    }

    public function isVariationParentRepricingStateYes()
    {
        return (int)$this->getVariationParentRepricingState() == self::VARIATION_PARENT_IS_REPRICING_STATE_ALL_YES;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getOnlineHandlingTime()
    {
        return (int)$this->getData('online_handling_time');
    }

    public function getOnlineRestockDate()
    {
        return $this->getData('online_restock_date');
    }

    // ---------------------------------------

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOnlineDetailsData()
    {
        return $this->getData('online_details_data');
    }

    // ---------------------------------------

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getDefectedMessages()
    {
        return $this->getSettings('defected_messages');
    }

    //########################################

    public function getSearchSettingsStatus()
    {
        return $this->getData('search_settings_status');
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getSearchSettingsData()
    {
        return $this->getSettings('search_settings_data');
    }

    // ---------------------------------------

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getGeneralIdSearchInfo()
    {
        return $this->getSettings('general_id_search_info');
    }

    public function isAllowedForRegularCustomers()
    {
        return $this->getAmazonSellingFormatTemplate()->isRegularCustomerAllowed();
    }

    public function isAllowedForBusinessCustomers()
    {
        if (!$this->configuration->isEnabledBusinessMode()) {
            return false;
        }

        if (!$this->getAmazonMarketplace()->isBusinessAvailable()) {
            return false;
        }

        if (!$this->getAmazonSellingFormatTemplate()->isBusinessCustomerAllowed()) {
            return false;
        }

        return true;
    }

    /**
     * @param bool $magentoMode
     *
     * @return int
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getQty($magentoMode = false)
    {
        if (
            $this->getVariationManager()->isPhysicalUnit()
            && $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            $variations = $this->getVariations(true);
            if (empty($variations)) {
                throw new \Ess\M2ePro\Model\Exception\Logic(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId(),
                    ]
                );
            }
            /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */
            $variation = reset($variations);

            return $variation->getChildObject()->getQty($magentoMode);
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\QtyCalculator $calculator */
        $calculator = $this->modelFactory->getObject('Amazon_Listing_Product_QtyCalculator');
        $calculator->setProduct($this->getParentObject());
        $calculator->setIsMagentoMode($magentoMode);

        return $calculator->getProductValue();
    }

    //########################################

    /**
     * @return float|int
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRegularPrice()
    {
        if (!$this->isAllowedForRegularCustomers()) {
            return null;
        }

        if (
            $this->getVariationManager()->isPhysicalUnit()
            && $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            $variations = $this->getVariations(true);
            if (empty($variations)) {
                throw new \Ess\M2ePro\Model\Exception\Logic(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId(),
                    ]
                );
            }
            /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */
            $variation = reset($variations);

            return $variation->getChildObject()->getRegularPrice();
        }

        $src = $this->getAmazonSellingFormatTemplate()->getRegularPriceSource();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator $calculator */
        $calculator = $this->amazonPriceCalculatorFactory->create();
        $calculator->setSource($src)->setProduct($this->getParentObject());
        $calculator->setModifier($this->getAmazonSellingFormatTemplate()->getRegularPriceModifier());
        $calculator->setVatPercent($this->getAmazonSellingFormatTemplate()->getRegularPriceVatPercent());

        return $calculator->getProductValue();
    }

    /**
     * @return float|int
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRegularMapPrice()
    {
        if (!$this->isAllowedForRegularCustomers()) {
            return null;
        }

        if (
            $this->getVariationManager()->isPhysicalUnit()
            && $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            $variations = $this->getVariations(true);
            if (empty($variations)) {
                throw new \Ess\M2ePro\Model\Exception(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId(),
                    ]
                );
            }
            /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */
            $variation = reset($variations);

            return $variation->getChildObject()->getRegularMapPrice();
        }

        $src = $this->getAmazonSellingFormatTemplate()->getRegularMapPriceSource();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator $calculator */
        $calculator = $this->amazonPriceCalculatorFactory->create();
        $calculator->setSource($src)->setProduct($this->getParentObject());

        return $calculator->getProductValue();
    }

    // ---------------------------------------

    /**
     * @return float|int
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRegularSalePrice()
    {
        if (!$this->isAllowedForRegularCustomers()) {
            return null;
        }

        if (
            $this->getVariationManager()->isPhysicalUnit()
            && $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            $variations = $this->getVariations(true);
            if (empty($variations)) {
                throw new \Ess\M2ePro\Model\Exception(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId(),
                    ]
                );
            }
            /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */
            $variation = reset($variations);

            return $variation->getChildObject()->getRegularSalePrice();
        }

        $src = $this->getAmazonSellingFormatTemplate()->getRegularSalePriceSource();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator $calculator */
        $calculator = $this->amazonPriceCalculatorFactory->create();
        $calculator->setSource($src)->setProduct($this->getParentObject());
        $calculator->setIsSalePrice(true);
        $calculator->setModifier($this->getAmazonSellingFormatTemplate()->getRegularSalePriceModifier());
        $calculator->setVatPercent($this->getAmazonSellingFormatTemplate()->getRegularPriceVatPercent());

        return $calculator->getProductValue();
    }

    /**
     * @return array|false
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRegularSalePriceInfo()
    {
        $price = $this->getRegularPrice();
        $salePrice = $this->getRegularSalePrice();

        if ($salePrice <= 0 || $salePrice >= $price) {
            return false;
        }

        $startDate = $this->getRegularSalePriceStartDate();
        $endDate = $this->getRegularSalePriceEndDate();

        if (!$startDate || !$endDate) {
            return false;
        }

        $startDateTimestamp = (int)$this->helperData
            ->createGmtDateTime($startDate)
            ->format('U');
        $endDateTimestamp = (int)$this->helperData
            ->createGmtDateTime($endDate)
            ->format('U');

        $currentTimestamp = (int)$this->helperData
            ->createGmtDateTime($this->helperData->getCurrentGmtDate(false, 'Y-m-d 00:00:00'))
            ->format('U');

        if (
            $currentTimestamp > $endDateTimestamp
            || $startDateTimestamp >= $endDateTimestamp
        ) {
            return false;
        }

        return [
            'price' => $salePrice,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    // ---------------------------------------

    private function getRegularSalePriceStartDate()
    {
        if (
            $this->getAmazonSellingFormatTemplate()->isRegularSalePriceModeSpecial()
            && $this->getMagentoProduct()->isGroupedType()
        ) {
            $magentoProduct = $this->getActualMagentoProduct();
        } elseif ($this->getAmazonSellingFormatTemplate()->isRegularPriceVariationModeParent()) {
            $magentoProduct = $this->getMagentoProduct();
        } else {
            $magentoProduct = $this->getActualMagentoProduct();
        }

        $date = null;

        if ($this->getAmazonSellingFormatTemplate()->isRegularSalePriceModeSpecial()) {
            $date = $magentoProduct->getSpecialPriceFromDate();
        } else {
            $src = $this->getAmazonSellingFormatTemplate()->getRegularSalePriceStartDateSource();

            $date = $src['value'];

            if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Template\SellingFormat::DATE_ATTRIBUTE) {
                $date = $magentoProduct->getAttributeValue($src['attribute']);
            }
        }

        if (strtotime($date) === false) {
            return false;
        }

        return $this->helperData
            ->createGmtDateTime($date)
            ->format('Y-m-d 00:00:00');
    }

    private function getRegularSalePriceEndDate()
    {
        if (
            $this->getAmazonSellingFormatTemplate()->isRegularSalePriceModeSpecial()
            && $this->getMagentoProduct()->isGroupedType()
        ) {
            $magentoProduct = $this->getActualMagentoProduct();
        } elseif ($this->getAmazonSellingFormatTemplate()->isRegularPriceVariationModeParent()) {
            $magentoProduct = $this->getMagentoProduct();
        } else {
            $magentoProduct = $this->getActualMagentoProduct();
        }

        $date = null;

        if ($this->getAmazonSellingFormatTemplate()->isRegularSalePriceModeSpecial()) {
            $date = $magentoProduct->getSpecialPriceToDate();

            $tempDate = new \DateTime($date, new \DateTimeZone('UTC'));
            $tempDate->modify('-1 day');
            $date = $tempDate->format('Y-m-d H:i:s');
        } else {
            $src = $this->getAmazonSellingFormatTemplate()->getRegularSalePriceEndDateSource();

            $date = $src['value'];

            if ($src['mode'] == \Ess\M2ePro\Model\Amazon\Template\SellingFormat::DATE_ATTRIBUTE) {
                $date = $magentoProduct->getAttributeValue($src['attribute']);
            }
        }

        if (strtotime($date) === false) {
            return false;
        }

        return $this->helperData
            ->createGmtDateTime($date)
            ->format('Y-m-d 00:00:00');
    }

    // ---------------------------------------

    /**
     * @return float|int
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBusinessPrice()
    {
        if (!$this->isAllowedForBusinessCustomers()) {
            return null;
        }

        if (
            $this->getVariationManager()->isPhysicalUnit()
            && $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            $variations = $this->getVariations(true);
            if (empty($variations)) {
                throw new \Ess\M2ePro\Model\Exception(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId(),
                    ]
                );
            }
            /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */
            $variation = reset($variations);

            return $variation->getChildObject()->getBusinessPrice();
        }

        $src = $this->getAmazonSellingFormatTemplate()->getBusinessPriceSource();

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator $calculator */
        $calculator = $this->amazonPriceCalculatorFactory->create();
        $calculator->setSource($src)->setProduct($this->getParentObject());
        $calculator->setModifier($this->getAmazonSellingFormatTemplate()->getBusinessPriceModifier());
        $calculator->setVatPercent($this->getAmazonSellingFormatTemplate()->getBusinessPriceVatPercent());

        return $calculator->getProductValue();
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBusinessDiscounts()
    {
        if (!$this->isAllowedForBusinessCustomers()) {
            return null;
        }

        if ($this->getAmazonSellingFormatTemplate()->isBusinessDiscountsModeNone()) {
            return [];
        }

        if (
            $this->getVariationManager()->isPhysicalUnit()
            && $this->getVariationManager()->getTypeModel()->isVariationProductMatched()
        ) {
            $variations = $this->getVariations(true);
            if (empty($variations)) {
                throw new \Ess\M2ePro\Model\Exception\Logic(
                    'There are no variations for a variation product.',
                    [
                        'listing_product_id' => $this->getId(),
                    ]
                );
            }
            /** @var \Ess\M2ePro\Model\Listing\Product\Variation $variation */
            $variation = reset($variations);

            return $variation->getChildObject()->getBusinessDiscounts();
        }

        if ($this->getAmazonSellingFormatTemplate()->isBusinessDiscountsModeTier()) {
            $src = $this->getAmazonSellingFormatTemplate()->getBusinessDiscountsSource();
            $src['tier_website_id'] = $this->getHelper('Magento\Store')
                                           ->getWebsite($this->getListing()->getStoreId())->getId();

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator $calculator */
            $calculator = $this->amazonPriceCalculatorFactory->create();
            $calculator->setSource($src)->setProduct($this->getParentObject());
            $calculator->setSourceModeMapping([
                \Ess\M2ePro\Model\Listing\Product\PriceCalculator::MODE_TIER =>
                    \Ess\M2ePro\Model\Amazon\Template\SellingFormat::BUSINESS_DISCOUNTS_MODE_TIER,
            ]);
            $calculator->setModifier($this->getAmazonSellingFormatTemplate()->getBusinessDiscountsTierModifier());
            $calculator->setVatPercent($this->getAmazonSellingFormatTemplate()->getBusinessPriceVatPercent());

            return array_slice(
                $calculator->getProductValue(),
                0,
                self::BUSINESS_DISCOUNTS_MAX_RULES_COUNT_ALLOWED,
                true
            );
        }

        /** @var \Ess\M2ePro\Model\Amazon\Template\SellingFormat\BusinessDiscount[] $businessDiscounts */
        $businessDiscounts = $this->getAmazonSellingFormatTemplate()->getBusinessDiscounts(true);
        if (empty($businessDiscounts)) {
            return [];
        }

        $resultValue = [];

        foreach ($businessDiscounts as $businessDiscount) {
            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\PriceCalculator $calculator */
            $calculator = $this->amazonPriceCalculatorFactory->create();
            $calculator->setSource($businessDiscount->getSource())->setProduct($this->getParentObject());
            $calculator->setSourceModeMapping([
                \Ess\M2ePro\Model\Listing\Product\PriceCalculator::MODE_PRODUCT =>
                    \Ess\M2ePro\Model\Amazon\Template\SellingFormat\BusinessDiscount::MODE_PRODUCT,
                \Ess\M2ePro\Model\Listing\Product\PriceCalculator::MODE_SPECIAL =>
                    \Ess\M2ePro\Model\Amazon\Template\SellingFormat\BusinessDiscount::MODE_SPECIAL,
                \Ess\M2ePro\Model\Listing\Product\PriceCalculator::MODE_ATTRIBUTE =>
                    \Ess\M2ePro\Model\Amazon\Template\SellingFormat\BusinessDiscount::MODE_ATTRIBUTE,
            ]);
            $calculator->setCoefficient($businessDiscount->getCoefficient());
            $calculator->setVatPercent($this->getAmazonSellingFormatTemplate()->getBusinessPriceVatPercent());

            $resultValue[$businessDiscount->getQty()] = $calculator->getProductValue();

            if (count($resultValue) >= self::BUSINESS_DISCOUNTS_MAX_RULES_COUNT_ALLOWED) {
                break;
            }
        }

        return $resultValue;
    }

    // ---------------------------------------

    public function mapChannelItemProduct()
    {
        $this->getResource()->mapChannelItemProduct($this);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isStoppedManually(): bool
    {
        return (bool)$this->getData('is_stopped_manually');
    }

    /**
     * @param bool $value
     *
     * @return void
     */
    public function setIsStoppedManually(bool $value): void
    {
        $this->setData('is_stopped_manually', $value);
    }

    // ---------------------------------------
}
