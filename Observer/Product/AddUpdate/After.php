<?php

namespace Ess\M2ePro\Observer\Product\AddUpdate;

use Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class After extends AbstractAddUpdate
{
    /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Factory */
    private $listingAutoActionsModeFactory;
    /** @var array */
    protected $listingsProductsChangedAttributes = [];
    /** @var array */
    protected $attributeAffectOnStoreIdCache = [];

    /** @var \Magento\Eav\Model\Config */
    protected $eavConfig;
    /** @var \Magento\Store\Model\StoreManager */
    protected $storeManager;
    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    public function __construct(
        \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Factory $listingAutoActionsModeFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->eavConfig = $eavConfig;
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
        $this->listingAutoActionsModeFactory = $listingAutoActionsModeFactory;
        parent::__construct($productFactory, $helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function beforeProcess()
    {
        parent::beforeProcess();

        if (!$this->isProxyExist()) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Before proxy should be defined earlier than after Action is performed.'
            );
        }

        if ($this->getProductId() <= 0) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Product ID should be defined for "after save" event.');
        }

        $this->reloadProduct();
    }

    // ---------------------------------------

    public function process()
    {
        if (!$this->isAddingProductProcess()) {
            $this->updateProductsNamesInLogs();

            if ($this->areThereAffectedItems()) {
                $this->performStatusChanges();
                $this->performPriceChanges();
                $this->performSpecialPriceChanges();
                $this->performSpecialPriceFromDateChanges();
                $this->performSpecialPriceToDateChanges();
                $this->performTierPriceChanges();
                $this->performTrackingAttributesChanges();
                $this->performDefaultQtyChanges();

                $this->addListingProductInstructions();

                $this->updateListingsProductsVariations();
            }
        } else {
            $this->performGlobalAutoActions();
        }

        $this->performWebsiteAutoActions();
        $this->performCategoryAutoActions();
    }

    //########################################

    protected function updateProductsNamesInLogs()
    {
        if (!$this->isAdminDefaultStoreId()) {
            return;
        }

        $name = $this->getProduct()->getName();

        if ($this->getProxy()->getData('name') == $name) {
            return;
        }

        $this->activeRecordFactory->getObject('Listing\Log')
                                  ->getResource()
                                  ->updateProductTitle($this->getProductId(), $name);
    }

    protected function updateListingsProductsVariations()
    {
        /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Updater[] $variationUpdatersByComponent */
        $variationUpdatersByComponent = [];

        /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProductsForProcess */
        $listingsProductsForProcess = [];

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            if (!isset($variationUpdatersByComponent[$listingProduct->getComponentMode()])) {
                $variationUpdaterModel = ucwords($listingProduct->getComponentMode())
                    . '\Listing\Product\Variation\Updater';
                /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Updater $variationUpdaterObject */
                $variationUpdaterObject = $this->modelFactory->getObject($variationUpdaterModel);
                $variationUpdatersByComponent[$listingProduct->getComponentMode()] = $variationUpdaterObject;
            }

            $listingsProductsForProcess[$listingProduct->getId()] = $listingProduct;
        }

        // for amazon and walmart, variation updater must not be called for parent and his children in one time
        foreach ($listingsProductsForProcess as $listingProduct) {
            if (!$listingProduct->isComponentModeAmazon() && !$listingProduct->isComponentModeWalmart()) {
                continue;
            }

            $channelListingProduct = $listingProduct->getChildObject();

            $variationManager = $channelListingProduct->getVariationManager();

            if (
                $variationManager->isRelationChildType() &&
                isset($listingsProductsForProcess[$variationManager->getVariationParentId()])
            ) {
                unset($listingsProductsForProcess[$listingProduct->getId()]);
            }
        }

        foreach ($listingsProductsForProcess as $listingProduct) {
            $listingProduct->getMagentoProduct()->enableCache();

            $variationUpdater = $variationUpdatersByComponent[$listingProduct->getComponentMode()];
            $variationUpdater->process($listingProduct);
        }

        foreach ($variationUpdatersByComponent as $variationUpdater) {
            /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Updater $variationUpdater */
            $variationUpdater->afterMassProcessEvent();
        }

        foreach ($listingsProductsForProcess as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            if ($listingProduct->isDeleted()) {
                continue;
            }

            $listingProduct->getMagentoProduct()->disableCache();
        }
    }

    //########################################

    protected function performStatusChanges()
    {
        $oldValue = (int)$this->getProxy()->getData('status');
        $newValue = (int)$this->getProduct()->getStatus();

        if ($oldValue == $newValue) {
            return;
        }

        $oldValue = ($oldValue == Status::STATUS_ENABLED) ? 'Enabled' : 'Disabled';
        $newValue = ($newValue == Status::STATUS_ENABLED) ? 'Enabled' : 'Disabled';

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $listingProductStoreId = $listingProduct->getListing()->getStoreId();

            if (!$this->isAttributeAffectOnStoreId('status', $listingProductStoreId)) {
                continue;
            }

            $this->listingsProductsChangedAttributes[$listingProduct->getId()][] = 'status';

            $this->logListingProductMessage(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_STATUS,
                $oldValue,
                $newValue
            );
        }
    }

    protected function performPriceChanges()
    {
        $oldValue = round((float)$this->getProxy()->getData('price'), 2);
        $newValue = round((float)$this->getProduct()->getPrice(), 2);

        if ($oldValue == $newValue) {
            return;
        }

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {
            $this->listingsProductsChangedAttributes[$listingProduct->getId()][] = 'price';

            $this->logListingProductMessage(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_PRICE,
                $oldValue,
                $newValue
            );
        }
    }

    protected function performSpecialPriceChanges()
    {
        $oldValue = round((float)$this->getProxy()->getData('special_price'), 2);
        $newValue = round((float)$this->getProduct()->getSpecialPrice(), 2);

        if ($oldValue == $newValue) {
            return;
        }

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {
            $this->listingsProductsChangedAttributes[$listingProduct->getId()][] = 'special_price';

            $this->logListingProductMessage(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_SPECIAL_PRICE,
                $oldValue,
                $newValue
            );
        }
    }

    protected function performSpecialPriceFromDateChanges()
    {
        $oldValue = $this->getProxy()->getData('special_price_from_date');
        $newValue = $this->getProduct()->getSpecialFromDate();

        if ($oldValue == $newValue) {
            return;
        }

        ($oldValue === null || $oldValue === false || $oldValue == '') && $oldValue = 'None';
        ($newValue === null || $newValue === false || $newValue == '') && $newValue = 'None';

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {
            $this->listingsProductsChangedAttributes[$listingProduct->getId()][] = 'special_price_from_date';

            $this->logListingProductMessage(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_FROM_DATE,
                $oldValue,
                $newValue
            );
        }
    }

    protected function performSpecialPriceToDateChanges()
    {
        $oldValue = $this->getProxy()->getData('special_price_to_date');
        $newValue = $this->getProduct()->getSpecialToDate();

        if ($oldValue == $newValue) {
            return;
        }

        ($oldValue === null || $oldValue === false || $oldValue == '') && $oldValue = 'None';
        ($newValue === null || $newValue === false || $newValue == '') && $newValue = 'None';

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {
            $this->listingsProductsChangedAttributes[$listingProduct->getId()][] = 'special_price_to_date';

            $this->logListingProductMessage(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_TO_DATE,
                $oldValue,
                $newValue
            );
        }
    }

    protected function performTierPriceChanges()
    {
        $oldValue = $this->getProxy()->getData('tier_price');
        $newValue = $this->getProduct()->getTierPrice();

        if ($oldValue == $newValue) {
            return;
        }

        $oldValue = $this->convertTierPriceForLog($oldValue);
        $newValue = $this->convertTierPriceForLog($newValue);

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {
            $this->listingsProductsChangedAttributes[$listingProduct->getId()][] = 'tier_price';

            $this->logListingProductMessage(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_TIER_PRICE,
                $oldValue,
                $newValue
            );
        }
    }

    // ---------------------------------------

    protected function performTrackingAttributesChanges()
    {
        foreach ($this->getProxy()->getAttributes() as $attributeCode => $attributeValue) {
            $oldValue = $attributeValue;
            $newValue = $this->getMagentoProduct()->getAttributeValue($attributeCode);

            if ($oldValue == $newValue) {
                continue;
            }

            foreach ($this->getAffectedListingsProductsByTrackingAttribute($attributeCode) as $listingProduct) {
                if (!$this->isAttributeAffectOnStoreId($attributeCode, $listingProduct->getListing()->getStoreId())) {
                    continue;
                }

                $this->listingsProductsChangedAttributes[$listingProduct->getId()][] = $attributeCode;

                $this->logListingProductMessage(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_CUSTOM_ATTRIBUTE,
                    $oldValue,
                    $newValue,
                    'of attribute "' . $attributeCode . '"'
                );
            }
        }
    }

    // ---------------------------------------

    protected function performDefaultQtyChanges()
    {
        if (!$this->getHelper('Magento_Product')->isGroupedType($this->getProduct()->getTypeId())) {
            return;
        }

        $values = $this->getProxy()->getData('default_qty');
        foreach ($this->getProduct()->getTypeInstance()->getAssociatedProducts($this->getProduct()) as $childProduct) {
            $sku = $childProduct->getSku();
            $newValue = (int)$childProduct->getQty();
            $oldValue = isset($values[$sku]) ? (int)$values[$sku] : 0;

            unset($values[$sku]);
            if ($oldValue == $newValue) {
                continue;
            }

            foreach ($this->getAffectedListingsProducts() as $listingProduct) {
                $this->listingsProductsChangedAttributes[$listingProduct->getId()][] = 'qty';

                $this->logListingProductMessage(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_QTY,
                    $oldValue,
                    $newValue,
                    "SKU {$sku}: Default QTY was changed."
                );
            }
        }

        //----------------------------------------

        foreach ($values as $sku => $defaultQty) {
            foreach ($this->getAffectedListingsProducts() as $listingProduct) {
                $this->listingsProductsChangedAttributes[$listingProduct->getId()][] = 'qty';

                $this->logListingProductMessage(
                    $listingProduct,
                    \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_QTY,
                    $defaultQty,
                    0,
                    "SKU {$sku} was removed from the Product Set."
                );
            }
        }
    }

    // ---------------------------------------

    protected function addListingProductInstructions()
    {
        foreach ($this->getAffectedListingsProducts() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel $changeProcessor */
            $changeProcessor = $this->modelFactory->getObject(
                ucfirst($listingProduct->getComponentMode()) . '_Magento_Product_ChangeProcessor'
            );
            $changeProcessor->setListingProduct($listingProduct);
            $changeProcessor->setDefaultInstructionTypes(
                [
                    ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_DATA_POTENTIALLY_CHANGED,
                ]
            );

            $changedAttributes = !empty($this->listingsProductsChangedAttributes[$listingProduct->getId()]) ?
                $this->listingsProductsChangedAttributes[$listingProduct->getId()] :
                [];

            $changeProcessor->process($changedAttributes);
        }
    }

    //########################################

    protected function performGlobalAutoActions()
    {
        $object = $this->listingAutoActionsModeFactory->createGlobalMode($this->getProduct());
        $object->synch();
    }

    protected function performWebsiteAutoActions()
    {
        $object = $this->listingAutoActionsModeFactory->createWebsiteMode($this->getProduct());

        $websiteIdsOld = $this->getProxy()->getWebsiteIds();
        $websiteIdsNew = $this->getProduct()->getWebsiteIds();

        // website for admin values
        $this->isAddingProductProcess() && $websiteIdsNew[] = 0;

        $addedWebsiteIds = array_diff($websiteIdsNew, $websiteIdsOld);
        foreach ($addedWebsiteIds as $websiteId) {
            $object->synchWithAddedWebsiteId($websiteId);
        }

        $deletedWebsiteIds = array_diff($websiteIdsOld, $websiteIdsNew);
        foreach ($deletedWebsiteIds as $websiteId) {
            $object->synchWithDeletedWebsiteId($websiteId);
        }
    }

    protected function performCategoryAutoActions()
    {
        $categoryIdsOld = $this->getProxy()->getCategoriesIds();
        $categoryIdsNew = $this->getProduct()->getCategoryIds();
        $addedCategories = array_diff($categoryIdsNew, $categoryIdsOld);
        $deletedCategories = array_diff($categoryIdsOld, $categoryIdsNew);

        $websiteIdsOld = $this->getProxy()->getWebsiteIds();
        $websiteIdsNew = $this->getProduct()->getWebsiteIds();
        $addedWebsites = array_diff($websiteIdsNew, $websiteIdsOld);
        $deletedWebsites = array_diff($websiteIdsOld, $websiteIdsNew);

        $websitesChanges = [
            // website for default store view
            0 => [
                'added' => $addedCategories,
                'deleted' => $deletedCategories,
            ],
        ];

        foreach ($this->storeManager->getWebsites() as $website) {
            $websiteId = (int)$website->getId();

            $websiteChanges = [
                'added' => [],
                'deleted' => [],
            ];

            // website has been enabled
            if (in_array($websiteId, $addedWebsites)) {
                $websiteChanges['added'] = $categoryIdsNew;
                // website is enabled
            } elseif (in_array($websiteId, $websiteIdsNew)) {
                $websiteChanges['added'] = $addedCategories;
            }

            // website has been disabled
            if (in_array($websiteId, $deletedWebsites)) {
                $websiteChanges['deleted'] = $categoryIdsOld;
                // website is enabled
            } elseif (in_array($websiteId, $websiteIdsNew)) {
                $websiteChanges['deleted'] = $deletedCategories;
            }

            $websitesChanges[$websiteId] = $websiteChanges;
        }

        $categoryAutoAction = $this->listingAutoActionsModeFactory->createCategoryMode($this->getProduct());
        foreach ($websitesChanges as $websiteId => $changes) {
            $categoryAutoAction->synchWithAddedCategoryId($websiteId, $changes['added']);
            $categoryAutoAction->synchWithDeletedCategoryId($websiteId, $changes['deleted']);
        }
    }

    //########################################

    protected function isAddingProductProcess()
    {
        return $this->getProxy()->getProductId() <= 0 && $this->getProductId() > 0;
    }

    // ---------------------------------------

    protected function isProxyExist()
    {
        $key = $this->getProductId() . '_' . $this->getStoreId();
        if (isset(\Ess\M2ePro\Observer\Product\AddUpdate\Before::$proxyStorage[$key])) {
            return true;
        }

        $key = $this
            ->getEvent()
            ->getProduct()
            ->getData(\Ess\M2ePro\Observer\Product\AddUpdate\Before::BEFORE_EVENT_KEY);

        return isset(\Ess\M2ePro\Observer\Product\AddUpdate\Before::$proxyStorage[$key]);
    }

    /**
     * @return \Ess\M2ePro\Observer\Product\AddUpdate\Before\Proxy
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getProxy()
    {
        if (!$this->isProxyExist()) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                'Before proxy should be defined earlier than after Action is performed.'
            );
        }

        $key = $this->getProductId() . '_' . $this->getStoreId();
        if (isset(\Ess\M2ePro\Observer\Product\AddUpdate\Before::$proxyStorage[$key])) {
            return \Ess\M2ePro\Observer\Product\AddUpdate\Before::$proxyStorage[$key];
        }

        $key = $this
            ->getEvent()
            ->getProduct()
            ->getData(\Ess\M2ePro\Observer\Product\AddUpdate\Before::BEFORE_EVENT_KEY);

        return \Ess\M2ePro\Observer\Product\AddUpdate\Before::$proxyStorage[$key];
    }

    //########################################

    protected function isAttributeAffectOnStoreId($attributeCode, $onStoreId)
    {
        $cacheKey = $attributeCode . '_' . $onStoreId;

        if (isset($this->attributeAffectOnStoreIdCache[$cacheKey])) {
            return $this->attributeAffectOnStoreIdCache[$cacheKey];
        }

        $attributeInstance = $this->eavConfig->getAttribute('catalog_product', $attributeCode);

        if (!($attributeInstance instanceof \Magento\Catalog\Model\ResourceModel\Eav\Attribute)) {
            return $this->attributeAffectOnStoreIdCache[$cacheKey] = false;
        }

        $attributeScope = (int)$attributeInstance->getData('is_global');

        if (
            $attributeScope == \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL ||
            $this->getStoreId() == $onStoreId
        ) {
            return $this->attributeAffectOnStoreIdCache[$cacheKey] = true;
        }

        if ($this->getStoreId() == \Magento\Store\Model\Store::DEFAULT_STORE_ID) {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->productFactory->create();
            $product->setStoreId($onStoreId);
            $product->load($this->getProductId());

            $scopeOverridden = $this->objectManager
                ->create(\Magento\Catalog\Model\Attribute\ScopeOverriddenValue::class);
            $isExistsValueForStore = $scopeOverridden->containsValue(
                \Magento\Catalog\Api\Data\ProductInterface::class,
                $product,
                $attributeCode,
                $onStoreId
            );

            return $this->attributeAffectOnStoreIdCache[$cacheKey] = !$isExistsValueForStore;
        }

        if ($attributeScope == \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_STORE) {
            return $this->attributeAffectOnStoreIdCache[$cacheKey] = false;
        }

        $affectedStoreIds = $this->storeManager->getStore($this->getStoreId())->getWebsite()->getStoreIds();
        $affectedStoreIds = array_map('intval', array_values(array_unique($affectedStoreIds)));

        return $this->attributeAffectOnStoreIdCache[$cacheKey] = in_array($onStoreId, $affectedStoreIds);
    }

    //########################################

    /**
     * @param $attributeCode
     *
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    protected function getAffectedListingsProductsByTrackingAttribute($attributeCode)
    {
        $result = [];

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel $changeProcessor */
            $changeProcessor = $this->modelFactory->getObject(
                ucfirst($listingProduct->getComponentMode()) . '_Magento_Product_ChangeProcessor'
            );
            $changeProcessor->setListingProduct($listingProduct);

            if (in_array($attributeCode, $changeProcessor->getTrackingAttributes())) {
                $result[] = $listingProduct;
            }
        }

        return $result;
    }

    //########################################

    protected function convertTierPriceForLog($tierPrice)
    {
        if (empty($tierPrice) || !is_array($tierPrice)) {
            return 'None';
        }

        $result = [];
        foreach ($tierPrice as $tierPriceData) {
            $result[] = sprintf(
                "[price = %s, qty = %s]",
                $tierPriceData["website_price"],
                $tierPriceData["price_qty"]
            );
        }

        return implode(",", $result);
    }

    //########################################

    protected function logListingProductMessage(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        $action,
        $oldValue,
        $newValue,
        $messagePostfix = ''
    ) {
        $log = $this->activeRecordFactory->getObject(
            ucfirst($listingProduct->getComponentMode()) . '\Listing\Log'
        );

        $oldValue = strlen($oldValue) > 150 ? substr($oldValue, 0, 150) . ' ...' : $oldValue;
        $newValue = strlen($newValue) > 150 ? substr($newValue, 0, 150) . ' ...' : $newValue;

        $messagePostfix = trim(trim($messagePostfix), '.');
        if (!empty($messagePostfix)) {
            $messagePostfix = ' ' . $messagePostfix;
        }

        if ($listingProduct->isComponentModeEbay() && is_array($listingProduct->getData('found_options_ids'))) {
            $collection = $this->activeRecordFactory->getObject('Listing_Product_Variation_Option')->getCollection()
                                                    ->addFieldToFilter(
                                                        'main_table.id',
                                                        ['in' => $listingProduct->getData('found_options_ids')]
                                                    );

            $additionalData = [];
            foreach ($collection as $listingProductVariationOption) {
                /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Option $listingProductVariationOption */
                $additionalData['variation_options'][$listingProductVariationOption
                    ->getAttribute()] = $listingProductVariationOption->getOption();
            }

            if (
                !empty($additionalData['variation_options']) &&
                $this->getHelper('Magento\Product')->isBundleType($collection->getFirstItem()->getProductType())
            ) {
                foreach ($additionalData['variation_options'] as $attribute => $option) {
                    $log->addProductMessage(
                        $listingProduct->getListingId(),
                        $listingProduct->getProductId(),
                        $listingProduct->getId(),
                        \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                        null,
                        $action,
                        $this->getHelper('Module\Log')->encodeDescription(
                            'From [%from%] to [%to%]' . $messagePostfix . '.',
                            ['!from' => $oldValue, '!to' => $newValue]
                        ),
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO,
                        ['variation_options' => [$attribute => $option]]
                    );
                }

                return;
            }

            $log->addProductMessage(
                $listingProduct->getListingId(),
                $listingProduct->getProductId(),
                $listingProduct->getId(),
                \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                null,
                $action,
                $this->getHelper('Module\Log')->encodeDescription(
                    'From [%from%] to [%to%]' . $messagePostfix . '.',
                    ['!from' => $oldValue, '!to' => $newValue]
                ),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO,
                $additionalData
            );

            return;
        }

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            null,
            $action,
            $this->getHelper('Module\Log')->encodeDescription(
                'From [%from%] to [%to%]' . $messagePostfix . '.',
                ['!from' => $oldValue, '!to' => $newValue]
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
        );
    }

    //########################################
}
