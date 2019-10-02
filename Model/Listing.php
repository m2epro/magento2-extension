<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

use \Ess\M2ePro\Model\Amazon\Listing\Product as AmazonProduct;
use \Ess\M2ePro\Model\Walmart\Listing\Product as WalmartProduct;

/**
 * @method \Ess\M2ePro\Model\Ebay\Listing|\Ess\M2ePro\Model\Amazon\Listing|
 * \Ess\M2ePro\Model\Walmart\Listing getChildObject()
 */
class Listing extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    const SOURCE_PRODUCTS_CUSTOM     = 1;
    const SOURCE_PRODUCTS_CATEGORIES = 2;

    const AUTO_MODE_NONE     = 0;
    const AUTO_MODE_GLOBAL   = 1;
    const AUTO_MODE_WEBSITE  = 2;
    const AUTO_MODE_CATEGORY = 3;

    const ADDING_MODE_NONE          = 0;
    const ADDING_MODE_ADD           = 1;

    const AUTO_ADDING_ADD_NOT_VISIBLE_NO   = 0;
    const AUTO_ADDING_ADD_NOT_VISIBLE_YES  = 1;

    const DELETING_MODE_NONE        = 0;
    const DELETING_MODE_STOP        = 1;
    const DELETING_MODE_STOP_REMOVE = 2;

    /**
     * @var \Ess\M2ePro\Model\Account
     */
    private $accountModel = null;

    /**
     * @var \Ess\M2ePro\Model\Marketplace
     */
    private $marketplaceModel = null;

    protected $productColFactory;

    //########################################

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productColFactory,
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
        $this->productColFactory = $productColFactory;
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

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Listing');
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        if ($this->isComponentModeEbay() && $this->getAccount()->getChildObject()->isModeSandbox()) {
            return false;
        }

        if (parent::isLocked()) {
            return true;
        }

        return (bool)$this->activeRecordFactory->getObject('Listing\Product')
            ->getCollection()
            ->addFieldToFilter('listing_id', $this->getId())
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED)
            ->getSize();
    }

    //########################################

    public function save($reloadOnCreate = false)
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('listing');
        return parent::save($reloadOnCreate);
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $products = $this->getProducts(true);
        foreach ($products as $product) {
            $product->delete();
        }

        $categoriesGroups = $this->getAutoCategoriesGroups(true);
        foreach ($categoriesGroups as $categoryGroup) {
            $categoryGroup->delete();
        }

        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode($this->getComponentMode());
        $tempLog->addListingMessage(
            $this->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_LISTING,
            // M2ePro\TRANSLATIONS
            // Listing was successfully deleted
            'Listing was successfully deleted',
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
        );

        $this->accountModel = null;
        $this->marketplaceModel = null;

        $this->deleteChildInstance();

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('listing');
        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        if ($this->accountModel === null) {
            $this->accountModel = $this->parentFactory->getCachedObjectLoaded(
                $this->getComponentMode(),
                'Account',
                $this->getAccountId()
            );
        }

        return $this->accountModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Account $instance
     */
    public function setAccount(\Ess\M2ePro\Model\Account $instance)
    {
         $this->accountModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        if ($this->marketplaceModel === null) {
            $this->marketplaceModel = $this->parentFactory->getCachedObjectLoaded(
                $this->getComponentMode(),
                'Marketplace',
                $this->getMarketplaceId()
            );
        }

        return $this->marketplaceModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Marketplace $instance
     */
    public function setMarketplace(\Ess\M2ePro\Model\Marketplace $instance)
    {
         $this->marketplaceModel = $instance;
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProducts($asObjects = false, array $filters = [])
    {
        $products = $this->getRelatedComponentItems('Listing\Product', 'listing_id', $asObjects, $filters);

        if ($asObjects) {
            foreach ($products as $product) {
                /** @var $product \Ess\M2ePro\Model\Listing\Product */
                $product->setListing($this);
            }
        }

        return $products;
    }

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAutoCategoriesGroups($asObjects = false, array $filters = [])
    {
        return $this->getRelatedComponentItems('Listing_Auto_Category_Group', 'listing_id', $asObjects, $filters);
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getAccountId()
    {
        return (int)$this->getData('account_id');
    }

    /**
     * @return int
     */
    public function getMarketplaceId()
    {
        return (int)$this->getData('marketplace_id');
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return (int)$this->getData('store_id');
    }

    // ---------------------------------------

    public function getCreateDate()
    {
        return $this->getData('create_date');
    }

    public function getUpdateDate()
    {
        return $this->getData('update_date');
    }

    //########################################

    /**
     * @return bool
     */
    public function isSourceProducts()
    {
        return (int)$this->getData('source_products') == self::SOURCE_PRODUCTS_CUSTOM;
    }

    /**
     * @return bool
     */
    public function isSourceCategories()
    {
        return (int)$this->getData('source_products') == self::SOURCE_PRODUCTS_CATEGORIES;
    }

    //########################################

    /**
     * @return int
     */
    public function getAutoMode()
    {
        return (int)$this->getData('auto_mode');
    }

    /**
     * @return bool
     */
    public function isAutoModeNone()
    {
        return $this->getAutoMode() == self::AUTO_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isAutoModeGlobal()
    {
        return $this->getAutoMode() == self::AUTO_MODE_GLOBAL;
    }

    /**
     * @return bool
     */
    public function isAutoModeWebsite()
    {
        return $this->getAutoMode() == self::AUTO_MODE_WEBSITE;
    }

    /**
     * @return bool
     */
    public function isAutoModeCategory()
    {
        return $this->getAutoMode() == self::AUTO_MODE_CATEGORY;
    }

    //########################################

    /**
     * @return bool
     */
    public function isAutoGlobalAddingAddNotVisibleYes()
    {
        return $this->getData('auto_global_adding_add_not_visible') == self::AUTO_ADDING_ADD_NOT_VISIBLE_YES;
    }

    /**
     * @return bool
     */
    public function isAutoWebsiteAddingAddNotVisibleYes()
    {
        return $this->getData('auto_website_adding_add_not_visible') == self::AUTO_ADDING_ADD_NOT_VISIBLE_YES;
    }

    //########################################

    /**
     * @return int
     */
    public function getAutoGlobalAddingMode()
    {
        return (int)$this->getData('auto_global_adding_mode');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isAutoGlobalAddingModeNone()
    {
        return $this->getAutoGlobalAddingMode() == self::ADDING_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isAutoGlobalAddingModeAdd()
    {
        return $this->getAutoGlobalAddingMode() == self::ADDING_MODE_ADD;
    }

    //########################################

    /**
     * @return int
     */
    public function getAutoWebsiteAddingMode()
    {
        return (int)$this->getData('auto_website_adding_mode');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isAutoWebsiteAddingModeNone()
    {
        return $this->getAutoWebsiteAddingMode() == self::ADDING_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isAutoWebsiteAddingModeAdd()
    {
        return $this->getAutoWebsiteAddingMode() == self::ADDING_MODE_ADD;
    }

    //########################################

    /**
     * @return int
     */
    public function getAutoWebsiteDeletingMode()
    {
        return (int)$this->getData('auto_website_deleting_mode');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isAutoWebsiteDeletingModeNone()
    {
        return $this->getAutoWebsiteDeletingMode() == self::DELETING_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isAutoWebsiteDeletingModeStop()
    {
        return $this->getAutoWebsiteDeletingMode() == self::DELETING_MODE_STOP;
    }

    /**
     * @return bool
     */
    public function isAutoWebsiteDeletingModeStopRemove()
    {
        return $this->getAutoWebsiteDeletingMode() == self::DELETING_MODE_STOP_REMOVE;
    }

    //########################################

    public function addProduct(
        $product,
        $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
        $checkingMode = false,
        $checkHasProduct = true,
        array $logAdditionalInfo = []
    ) {
        $productId = $product instanceof \Magento\Catalog\Model\Product ?
                        (int)$product->getId() : (int)$product;

        if ($checkHasProduct && $this->hasProduct($productId)) {
            return false;
        }

        if ($checkingMode) {
            return true;
        }

        $data = [
            'listing_id' => $this->getId(),
            'product_id' => $productId,
            'status'     => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN
        ];

        $listingProductTemp = $this->parentFactory->getObject($this->getComponentMode(), 'Listing\Product')
            ->setData($data)->save();

        $listingProductTemp->getChildObject()->afterSaveNewEntity();

        $variationUpdaterModel = ucwords($this->getComponentMode()).'\Listing\Product\Variation\Updater';
        /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Updater $variationUpdaterObject */
        $variationUpdaterObject = $this->modelFactory->getObject($variationUpdaterModel);
        $variationUpdaterObject->process($listingProductTemp);
        $variationUpdaterObject->afterMassProcessEvent();

        // Add message for listing log
        // ---------------------------------------
        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode($this->getComponentMode());
        $tempLog->addProductMessage(
            $this->getId(),
            $productId,
            $listingProductTemp->getId(),
            $initiator,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_ADD_PRODUCT_TO_LISTING,
            // M2ePro\TRANSLATIONS
            // Product was successfully Added
            'Product was successfully Added',
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW,
            $logAdditionalInfo
        );
        // ---------------------------------------

        return $listingProductTemp;
    }

    // ---------------------------------------

    public function addProductsFromCategory(
        $categoryId,
        $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN
    ) {
        $categoryProductsArray = $this->getProductsFromCategory($categoryId);
        foreach ($categoryProductsArray as $productTemp) {
            $this->addProduct($productTemp, $initiator);
        }
    }

    public function getProductsFromCategory($categoryId, $hideProductsPresentedInAnotherListings = false)
    {
        $collection = $this->productColFactory->create();

        $connection = $this->getResource()->getConnection();

        if ($hideProductsPresentedInAnotherListings) {
            $table = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();
            $dbSelect = $connection->select()
                ->from($table, new \Zend_Db_Expr('DISTINCT `product_id`'))
                ->where('`component_mode` = ?', (string)$this->getComponentMode());

            $collection->getSelect()->where('`e`.`entity_id` NOT IN ('.$dbSelect->__toString().')');
        }

        $table = $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('catalog_category_product');
        $dbSelect = $connection->select()
            ->from($table, new \Zend_Db_Expr('DISTINCT `product_id`'))
            ->where("`category_id` = ?", (int)$categoryId);

        $collection->getSelect()->where('`e`.`entity_id` IN ('.$dbSelect->__toString().')');

        $sqlQuery = $collection->getSelect()->__toString();

        $categoryProductsArray = $connection->fetchCol($sqlQuery);

        return (array)$categoryProductsArray;
    }

    //########################################

    /**
     * @param int $productId
     * @return bool
     */
    public function hasProduct($productId)
    {
        return !empty($this->getProducts(false, ['product_id'=>$productId]));
    }

    public function removeDeletedProduct($product)
    {
        $productId = $product instanceof \Magento\Catalog\Model\Product ?
                        (int)$product->getId() : (int)$product;

        $processedListings = [];

        // Delete Products
        // ---------------------------------------
        $listingsProducts = $this->activeRecordFactory->getObject('Listing\Product')->getCollection()
                                    ->addFieldToFilter('product_id', $productId)
                                    ->getItems();

        $listingsProductsForRemove = [];

        /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
        foreach ($listingsProducts as $listingProduct) {
            if (!isset($listingsProductsForRemove[$listingProduct->getId()])) {
                $listingProduct->deleteProcessingLocks();
                $listingProduct->isStoppable() && $this->activeRecordFactory->getObject('StopQueue')->add(
                    $listingProduct
                );
                $listingProduct->setStatus(\Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED)->save();

                if ($listingProduct->isComponentModeAmazon() || $listingProduct->isComponentModeWalmart()) {
                    /** @var AmazonProduct|WalmartProduct $componentListingProduct */
                    $componentListingProduct = $listingProduct->getChildObject();
                    $variationManager = $componentListingProduct->getVariationManager();

                    if (!$variationManager->isRelationChildType() ||
                        !isset($listingsProducts[$variationManager->getVariationParentId()])) {
                        $listingsProductsForRemove[$listingProduct->getId()] = $listingProduct;
                    }
                } else {
                    $listingsProductsForRemove[$listingProduct->getId()] = $listingProduct;
                }
            }

            $listingId = $listingProduct->getListingId();
            $componentMode = $listingProduct->getComponentMode();

            if (isset($processedListings[$listingId.'_'.$componentMode])) {
                continue;
            }

            $processedListings[$listingId.'_'.$componentMode] = 1;

            $this->activeRecordFactory->getObject('Listing\Log')
                ->setComponentMode($componentMode)
                ->addProductMessage(
                    $listingId,
                    $productId,
                    $listingProduct->getId(),
                    \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                    null,
                    \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_MAGENTO,
                    null,
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
                );
        }

        $processedListings = [];

        // Delete Options
        // ---------------------------------------
        $variationOptions = $this->activeRecordFactory->getObject('Listing_Product_Variation_Option')
                                    ->getCollection()
                                    ->addFieldToFilter('product_id', $productId)
                                    ->getItems();

        $processedVariationsIds = [];

        /** @var $variationOption \Ess\M2ePro\Model\Listing\Product\Variation\Option */
        foreach ($variationOptions as $variationOption) {
            if (in_array($variationOption->getListingProductVariationId(), $processedVariationsIds)) {
                continue;
            }

            $processedVariationsIds[] = $variationOption->getListingProductVariationId();

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
            $listingProduct = $variationOption->getListingProduct();

            if ($variationOption->isComponentModeEbay()) {

                /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Variation $ebayVariation */
                $variation = $variationOption->getListingProductVariation();
                $ebayVariation = $variation->getChildObject();

                if (!$ebayVariation->isNotListed()) {
                    $additionalData = $listingProduct->getAdditionalData();
                    $variationsThatCanNotBeDeleted = isset($additionalData['variations_that_can_not_be_deleted'])
                        ? $additionalData['variations_that_can_not_be_deleted'] : [];

                    $specifics = [];

                    foreach ($variation->getOptions(true, [], true, false) as $option) {
                        $specifics[$option->getAttribute()] = $option->getOption();
                    }

                    $tempVariation = [
                        'qty' => 0,
                        'price' => $ebayVariation->getOnlinePrice(),
                        'sku' => $ebayVariation->getOnlineSku(),
                        'add' => 0,
                        'delete' => 1,
                        'specifics' => $specifics,
                        'has_sales' => true
                    ];

                    if ($ebayVariation->isDelete()) {
                        $tempVariation['sku'] = 'del-' . sha1(microtime(1).$ebayVariation->getOnlineSku());
                    }

                    $specificsReplacements = $listingProduct->getChildObject()->getVariationSpecificsReplacements();
                    if (!empty($specificsReplacements)) {
                        $tempVariation['variations_specifics_replacements'] = $specificsReplacements;
                    }

                    $variationAdditionalData = $variation->getAdditionalData();
                    if (isset($variationAdditionalData['online_product_details'])) {
                        $tempVariation['details'] = $variationAdditionalData['online_product_details'];
                    }

                    $variationsThatCanNotBeDeleted[] = $tempVariation;
                    $additionalData['variations_that_can_not_be_deleted'] = $variationsThatCanNotBeDeleted;

                    $listingProduct->setSettings('additional_data', $additionalData)->save();
                }

                if ($listingProduct->getMagentoProduct()->isConfigurableType()) {
                    $listingProduct->getMagentoProduct()->getTypeInstance()->cleanProductCache(
                        $listingProduct->getMagentoProduct()->getProduct()
                    );
                }

                $variation->delete();
            } else {
                $listingProduct->deleteProcessingLocks();

                if ($listingProduct->isStoppable()) {
                    $this->activeRecordFactory->getObject('StopQueue')->add($listingProduct);
                    $listingProduct->setStatus(\Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED)->save();
                }

                $listingsProductsForRemove[$listingProduct->getId()] = $listingProduct;
            }

            $listingId = $listingProduct->getListingId();
            $componentMode = $listingProduct->getComponentMode();

            if (isset($processedListings[$listingId.'_'.$componentMode])) {
                continue;
            }

            $processedListings[$listingId.'_'.$componentMode] = 1;

            $this->activeRecordFactory->getObject('Listing\Log')
                ->setComponentMode($componentMode)
                ->addProductMessage(
                    $listingId,
                    $productId,
                    $listingProduct->getId(),
                    \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                    null,
                    \Ess\M2ePro\Model\Listing\Log::ACTION_DELETE_PRODUCT_FROM_MAGENTO,
                    // M2ePro\TRANSLATIONS
                                    // Variation Option was deleted. Item was reset.
                                    'Variation Option was deleted. Item was reset.',
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
                );
        }

        $parentListingProductsForRemove = [];

        foreach ($listingsProductsForRemove as $listingProduct) {
            if ($listingProduct->isComponentModeAmazon() || $listingProduct->isComponentModeWalmart()) {
                /** @var AmazonProduct|WalmartProduct $componentListingProduct */
                $componentListingProduct = $listingProduct->getChildObject();
                $variationManager = $componentListingProduct->getVariationManager();

                if ($variationManager->isRelationChildType()) {
                    /** @var AmazonProduct|WalmartProduct $parentProduct */
                    $parentProduct = $variationManager->getTypeModel()->getParentListingProduct()->getChildObject();
                    $listingProduct->delete();
                    $parentProduct->getVariationManager()->getTypeModel()->getProcessor()->process();
                    continue;
                }

                if ($variationManager->isVariationParent()) {
                    $parentListingProductsForRemove[] = $listingProduct;
                    continue;
                }
            }

            $listingProduct->delete();
        }

        foreach ($parentListingProductsForRemove as $listingProduct) {
            $listingProduct->delete();
        }
        // ---------------------------------------
    }

    //########################################

    public function getTrackingAttributes()
    {
        return $this->getChildObject()->getTrackingAttributes();
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
