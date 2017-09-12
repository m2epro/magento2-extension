<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Product\AddUpdate;

use \Magento\Catalog\Model\Product\Attribute\Source\Status;

class After extends AbstractAddUpdate
{
    private $eavConfig;
    private $storeManager;
    private $objectManager;
    private $attributeAffectOnStoreIdCache = array();

    //########################################

    public function __construct(
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager
    )
    {
        $this->eavConfig = $eavConfig;
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
        parent::__construct($productFactory, $helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function beforeProcess()
    {
        parent::beforeProcess();

        if (!$this->isProxyExist()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Before proxy should be defined earlier than after Action
                is performed.');
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

                $this->activeRecordFactory->getObject('ProductChange')->addUpdateAction(
                    $this->getProductId(),
                    \Ess\M2ePro\Model\ProductChange::INITIATOR_OBSERVER
                );

                $this->performStatusChanges();
                $this->performPriceChanges();
                $this->performSpecialPriceChanges();
                $this->performSpecialPriceFromDateChanges();
                $this->performSpecialPriceToDateChanges();
                $this->performTierPriceChanges();

                $this->performTrackingAttributesChanges();
                $this->updateListingsProductsVariations();
            }

        } else {
            $this->performGlobalAutoActions();
        }

        $this->performWebsiteAutoActions();
        $this->performCategoryAutoActions();
    }

    //########################################

    private function updateProductsNamesInLogs()
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

    private function updateListingsProductsVariations()
    {
        /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Updater[] $variationUpdatersByComponent */
        $variationUpdatersByComponent = array();

        /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProductsForProcess */
        $listingsProductsForProcess   = array();

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            if (!isset($variationUpdatersByComponent[$listingProduct->getComponentMode()])) {
                $variationUpdaterModel = ucwords($listingProduct->getComponentMode())
                                         .'\Listing\Product\Variation\Updater';
                /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Updater $variationUpdaterObject */
                $variationUpdaterObject = $this->modelFactory->getObject($variationUpdaterModel);
                $variationUpdatersByComponent[$listingProduct->getComponentMode()] = $variationUpdaterObject;
            }

            $listingsProductsForProcess[$listingProduct->getId()] = $listingProduct;
        }

        // for amazon, variation updater must not be called for parent and his children in one time
        foreach ($listingsProductsForProcess as $listingProduct) {
            if (!$listingProduct->isComponentModeAmazon()) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            $variationManager = $amazonListingProduct->getVariationManager();

            if ($variationManager->isRelationChildType() &&
                isset($listingsProductsForProcess[$variationManager->getVariationParentId()])) {

                unset($listingsProductsForProcess[$listingProduct->getId()]);
            }
        }

        foreach ($listingsProductsForProcess as $listingProduct) {
            $listingProduct->getMagentoProduct()->enableCache();

            $variationUpdater = $variationUpdatersByComponent[$listingProduct->getComponentMode()];
            $variationUpdater->process($listingProduct);
        }

        foreach ($variationUpdatersByComponent as $variationUpdater) {
            $variationUpdater->afterMassProcessEvent();
        }

        foreach ($listingsProductsForProcess as $listingProduct) {
            if ($listingProduct->isDeleted()) {
                continue;
            }

            $listingProduct->getMagentoProduct()->disableCache();
        }
    }

    //########################################

    private function performStatusChanges()
    {
        $oldValue = (int)$this->getProxy()->getData('status');
        $newValue = (int)$this->getProduct()->getStatus();

        // M2ePro\TRANSLATIONS
        // Enabled
        // Disabled

        $oldValue = ($oldValue == Status::STATUS_ENABLED) ? 'Enabled' : 'Disabled';
        $newValue = ($newValue == Status::STATUS_ENABLED) ? 'Enabled' : 'Disabled';

        $changedStores = array();

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $listingProductStoreId = $listingProduct->getListing()->getStoreId();

            if (!$this->isAttributeAffectOnStoreId('status',$listingProductStoreId)) {
                continue;
            }

            if (!$this->updateProductChangeRecord('status',$listingProductStoreId,$oldValue,$newValue) ||
                $oldValue == $newValue) {
                continue;
            }

            $changedStores[$listingProductStoreId] = true;

            $this->logListingProductMessage($listingProduct,
                                            \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_STATUS,
                                            $oldValue, $newValue);
        }
    }

    private function performPriceChanges()
    {
        $oldValue = round((float)$this->getProxy()->getData('price'),2);
        $newValue = round((float)$this->getProduct()->getPrice(),2);

        if (!$this->updateProductChangeRecord('price',NULL,$oldValue,$newValue) ||
            $oldValue == $newValue) {
            return;
        }

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $this->logListingProductMessage($listingProduct,
                                            \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_PRICE,
                                            $oldValue, $newValue);
        }
    }

    private function performSpecialPriceChanges()
    {
        $oldValue = round((float)$this->getProxy()->getData('special_price'),2);
        $newValue = round((float)$this->getProduct()->getSpecialPrice(),2);

        if (!$this->updateProductChangeRecord('special_price',NULL,$oldValue,$newValue) ||
            $oldValue == $newValue) {
            return;
        }

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $this->logListingProductMessage($listingProduct,
                                            \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_SPECIAL_PRICE,
                                            $oldValue, $newValue);
        }
    }

    private function performSpecialPriceFromDateChanges()
    {
        $oldValue = $this->getProxy()->getData('special_price_from_date');
        $newValue = $this->getProduct()->getSpecialFromDate();

        if (!$this->updateProductChangeRecord('special_price_from_date',NULL,$oldValue,$newValue) ||
            $oldValue == $newValue) {
            return;
        }

        // M2ePro\TRANSLATIONS
        // None

        (is_null($oldValue) || $oldValue === false || $oldValue == '') && $oldValue = 'None';
        (is_null($newValue) || $newValue === false || $newValue == '') && $newValue = 'None';

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $this->logListingProductMessage(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_FROM_DATE,
                $oldValue, $newValue
            );
        }
    }

    private function performSpecialPriceToDateChanges()
    {
        $oldValue = $this->getProxy()->getData('special_price_to_date');
        $newValue = $this->getProduct()->getSpecialToDate();

        if (!$this->updateProductChangeRecord('special_price_to_date',NULL,$oldValue,$newValue) ||
            $oldValue == $newValue) {
            return;
        }

        // M2ePro\TRANSLATIONS
        // None

        (is_null($oldValue) || $oldValue === false || $oldValue == '') && $oldValue = 'None';
        (is_null($newValue) || $newValue === false || $newValue == '') && $newValue = 'None';

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $this->logListingProductMessage(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_SPECIAL_PRICE_TO_DATE,
                $oldValue, $newValue
            );
        }
    }

    private function performTierPriceChanges()
    {
        $oldValue = $this->getProxy()->getData('tier_price');
        $newValue = $this->getProduct()->getTierPrice();

        if ($oldValue == $newValue) {
            return;
        }

        // M2ePro_TRANSLATIONS
        // None

        $oldValue = $this->convertTierPriceForLog($oldValue);
        $newValue = $this->convertTierPriceForLog($newValue);

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

            $this->logListingProductMessage(
                $listingProduct,
                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_PRODUCT_TIER_PRICE,
                $oldValue, $newValue
            );
        }
    }

    // ---------------------------------------

    private function performTrackingAttributesChanges()
    {
        foreach ($this->getProxy()->getAttributes() as $attributeCode => $attributeValue) {

            $oldValue = $attributeValue;
            $newValue = $this->getMagentoProduct()->getAttributeValue($attributeCode);

            $changedStores = array();

            foreach ($this->getAffectedListingsProductsByTrackingAttribute($attributeCode) as $listingProduct) {

                /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */

                $listingProductStoreId = $listingProduct->getListing()->getStoreId();

                if (!$this->isAttributeAffectOnStoreId($attributeCode,$listingProductStoreId)) {
                    continue;
                }

                if (!$this->updateProductChangeRecord($attributeCode,$listingProductStoreId,$oldValue,$newValue) ||
                    $oldValue == $newValue) {
                    continue;
                }

                $changedStores[$listingProductStoreId] = true;

                $this->logListingProductMessage($listingProduct,
                                                \Ess\M2ePro\Model\Listing\Log::ACTION_CHANGE_CUSTOM_ATTRIBUTE,
                                                $oldValue, $newValue, 'of attribute "'.$attributeCode.'"');
            }
        }
    }

    //########################################

    private function performGlobalAutoActions()
    {
        /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\GlobalMode $object */
        $object = $this->modelFactory->getObject('Listing\Auto\Actions\Mode\GlobalMode');
        $object->setProduct($this->getProduct());
        $object->synch();
    }

    private function performWebsiteAutoActions()
    {
        /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Website $object */
        $object = $this->modelFactory->getObject('Listing\Auto\Actions\Mode\Website');
        $object->setProduct($this->getProduct());

        $websiteIdsOld = $this->getProxy()->getWebsiteIds();
        $websiteIdsNew = $this->getProduct()->getWebsiteIds();

        // website for admin values
        $this->isAddingProductProcess() && $websiteIdsNew[] = 0;

        $addedWebsiteIds = array_diff($websiteIdsNew,$websiteIdsOld);
        foreach ($addedWebsiteIds as $websiteId) {
            $object->synchWithAddedWebsiteId($websiteId);
        }

        $deletedWebsiteIds = array_diff($websiteIdsOld,$websiteIdsNew);
        foreach ($deletedWebsiteIds as $websiteId) {
            $object->synchWithDeletedWebsiteId($websiteId);
        }
    }

    private function performCategoryAutoActions()
    {
        /** @var \Ess\M2ePro\Model\Listing\Auto\Actions\Mode\Category $object */
        $object = $this->modelFactory->getObject('Listing\Auto\Actions\Mode\Category');
        $object->setProduct($this->getProduct());

        $categoryIdsOld = $this->getProxy()->getCategoriesIds();
        $categoryIdsNew = $this->getProduct()->getCategoryIds();
        $addedCategories = array_diff($categoryIdsNew,$categoryIdsOld);
        $deletedCategories = array_diff($categoryIdsOld,$categoryIdsNew);

        $websiteIdsOld = $this->getProxy()->getWebsiteIds();
        $websiteIdsNew  = $this->getProduct()->getWebsiteIds();
        $addedWebsites = array_diff($websiteIdsNew, $websiteIdsOld);
        $deletedWebsites = array_diff($websiteIdsOld, $websiteIdsNew);

        $websitesChanges = array(
            // website for default store view
            0 => array(
                'added' => $addedCategories,
                'deleted' => $deletedCategories
            )
        );

        foreach ($this->storeManager->getWebsites() as $website) {

            $websiteId = (int)$website->getId();

            $websiteChanges = array(
                'added' => array(),
                'deleted' => array()
            );

            // website has been enabled
            if (in_array($websiteId,$addedWebsites)) {
                $websiteChanges['added'] = $categoryIdsNew;
            // website is enabled
            } else if (in_array($websiteId,$websiteIdsNew)) {
                $websiteChanges['added'] = $addedCategories;
            }

            // website has been disabled
            if (in_array($websiteId,$deletedWebsites)) {
                $websiteChanges['deleted'] = $categoryIdsOld;
                // website is enabled
            } else if (in_array($websiteId,$websiteIdsNew)) {
                $websiteChanges['deleted'] = $deletedCategories;
            }

            $websitesChanges[$websiteId] = $websiteChanges;
        }

        foreach ($websitesChanges as $websiteId => $changes) {

            foreach ($changes['added'] as $categoryId) {
                $object->synchWithAddedCategoryId($categoryId,$websiteId);
            }

            foreach ($changes['deleted'] as $categoryId) {
                $object->synchWithDeletedCategoryId($categoryId,$websiteId);
            }
        }
    }

    //########################################

    protected function isAddingProductProcess()
    {
        return $this->getProxy()->getProductId() <= 0 && $this->getProductId() > 0;
    }

    // ---------------------------------------

    private function isProxyExist()
    {
        $key = $this->getProductId().'_'.$this->getStoreId();
        if (isset(\Ess\M2ePro\Observer\Product\AddUpdate\Before::$proxyStorage[$key])) {
            return true;
        }

        $key = $this->getEvent()->getProduct()->getData('before_event_key');
        return isset(\Ess\M2ePro\Observer\Product\AddUpdate\Before::$proxyStorage[$key]);
    }

    /**
     * @return \Ess\M2ePro\Observer\Product\AddUpdate\Before\Proxy
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    private function getProxy()
    {
        if (!$this->isProxyExist()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Before proxy should be defined earlier than after Action
                is performed.');
        }

        $key = $this->getProductId().'_'.$this->getStoreId();
        if (isset(\Ess\M2ePro\Observer\Product\AddUpdate\Before::$proxyStorage[$key])) {
            return \Ess\M2ePro\Observer\Product\AddUpdate\Before::$proxyStorage[$key];
        }

        $key = $this->getEvent()->getProduct()->getData('before_event_key');
        return \Ess\M2ePro\Observer\Product\AddUpdate\Before::$proxyStorage[$key];
    }

    //########################################

    private function updateProductChangeRecord($attributeCode, $storeId, $oldValue, $newValue)
    {
        return $this->activeRecordFactory->getObject('ProductChange')->updateAttribute(
            $this->getProductId(),
            $attributeCode,
            $oldValue,
            $newValue,
            \Ess\M2ePro\Model\ProductChange::INITIATOR_OBSERVER,
            $storeId
        );
    }

    private function isAttributeAffectOnStoreId($attributeCode, $onStoreId)
    {
        $cacheKey = $attributeCode.'_'.$onStoreId;

        if (isset($this->attributeAffectOnStoreIdCache[$cacheKey])) {
            return $this->attributeAffectOnStoreIdCache[$cacheKey];
        }

        $attributeInstance = $this->eavConfig->getAttribute('catalog_product',$attributeCode);

        if (!($attributeInstance instanceof \Magento\Catalog\Model\ResourceModel\Eav\Attribute)) {
            return $this->attributeAffectOnStoreIdCache[$cacheKey] = false;
        }

        $attributeScope = (int)$attributeInstance->getData('is_global');

        if ($attributeScope == \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL ||
            $this->getStoreId() == $onStoreId) {
            return $this->attributeAffectOnStoreIdCache[$cacheKey] = true;
        }

        if ($this->getStoreId() == \Magento\Store\Model\Store::DEFAULT_STORE_ID) {

            /** @var \Magento\Catalog\Model\Product $product */
            $product = $this->productFactory->create();
            $product->setStoreId($onStoreId);
            $product->load($this->getProductId());

            $scopeOverridden = $this->objectManager->create('Magento\Catalog\Model\Attribute\ScopeOverriddenValue');
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
        $affectedStoreIds = array_map('intval',array_values(array_unique($affectedStoreIds)));

        return $this->attributeAffectOnStoreIdCache[$cacheKey] = in_array($onStoreId,$affectedStoreIds);
    }

    //########################################

    private function getAffectedListingsProductsByTrackingAttribute($attributeCode)
    {
        $result = array();

        foreach ($this->getAffectedListingsProducts() as $listingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
            if (in_array($attributeCode,$listingProduct->getTrackingAttributes())) {
                $result[] = $listingProduct;
            }
        }

        return $result;
    }

    //########################################

    private function convertTierPriceForLog($tierPrice)
    {
        if (empty($tierPrice) || !is_array($tierPrice)) {
            return 'None';
        }

        $result = [];
        foreach ($tierPrice as $tierPriceData) {
            $result[] = sprintf("[price = %s, qty = %s]",
                $tierPriceData["website_price"],
                $tierPriceData["price_qty"]
            );
        }

        return implode(",", $result);
    }

    //########################################

    private function logListingProductMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct, $action,
                                              $oldValue, $newValue, $messagePostfix = '')
    {
        // M2ePro\TRANSLATIONS
        // From [%from%] to [%to%].

        $log = $this->activeRecordFactory->getObject(
            ucfirst($listingProduct->getComponentMode()).'\Listing\Log'
        );

        $oldValue = strlen($oldValue) > 150 ? substr($oldValue, 0, 150) . ' ...' : $oldValue;
        $newValue = strlen($newValue) > 150 ? substr($newValue, 0, 150) . ' ...' : $newValue;

        $messagePostfix = trim(trim($messagePostfix), '.');
        if (!empty($messagePostfix)) {
            $messagePostfix = ' '.$messagePostfix;
        }

        if ($listingProduct->isComponentModeEbay() && is_array($listingProduct->getData('found_options_ids'))) {

            $collection = $this->activeRecordFactory->getObject('Listing\Product\Variation\Option')->getCollection()
                ->addFieldToFilter('main_table.id', array('in' => $listingProduct->getData('found_options_ids')));

            $additionalData = array();
            foreach ($collection as $listingProductVariationOption) {
                /** @var \Ess\M2ePro\Model\Listing\Product\Variation\Option $listingProductVariationOption  */
                $additionalData['variation_options'][$listingProductVariationOption
                    ->getAttribute()] = $listingProductVariationOption->getOption();
            }

            if (!empty($additionalData['variation_options']) &&
                $this->getHelper('Magento\Product')->isBundleType($collection->getFirstItem()->getProductType())) {

                foreach ($additionalData['variation_options'] as $attribute => $option) {
                    $log->addProductMessage(
                        $listingProduct->getListingId(),
                        $listingProduct->getProductId(),
                        $listingProduct->getId(),
                        \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                        NULL,
                        $action,
                        $this->getHelper('Module\Log')->encodeDescription(
                            'From [%from%] to [%to%]'.$messagePostfix.'.',
                            array('!from'=>$oldValue,'!to'=>$newValue)
                        ),
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
                        \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW,
                        array('variation_options' => array($attribute => $option))
                    );
                }

                return;
            }

            $log->addProductMessage(
                $listingProduct->getListingId(),
                $listingProduct->getProductId(),
                $listingProduct->getId(),
                \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
                NULL,
                $action,
                $this->getHelper('Module\Log')->encodeDescription(
                    'From [%from%] to [%to%]'.$messagePostfix.'.',
                    array('!from'=>$oldValue,'!to'=>$newValue)
                ),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW,
                $additionalData
            );

            return;
        }

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            NULL,
            $action,
            $this->getHelper('Module\Log')->encodeDescription(
                'From [%from%] to [%to%]'.$messagePostfix.'.',
                array('!from'=>$oldValue,'!to'=>$newValue)
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW
        );
    }

    //########################################
}