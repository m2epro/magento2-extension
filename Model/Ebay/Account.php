<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay;

use Ess\M2ePro\Model\Exception\Logic;

/**
 * Class \Ess\M2ePro\Model\Ebay\Account
 */
class Account extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Ebay\AbstractModel
{
    const MODE_SANDBOX = 0;
    const MODE_PRODUCTION = 1;

    const FEEDBACKS_RECEIVE_NO = 0;
    const FEEDBACKS_RECEIVE_YES = 1;

    const FEEDBACKS_AUTO_RESPONSE_NONE = 0;
    const FEEDBACKS_AUTO_RESPONSE_CYCLED = 1;
    const FEEDBACKS_AUTO_RESPONSE_RANDOM = 2;

    const FEEDBACKS_AUTO_RESPONSE_ONLY_POSITIVE_NO = 0;
    const FEEDBACKS_AUTO_RESPONSE_ONLY_POSITIVE_YES = 1;

    const OTHER_LISTINGS_SYNCHRONIZATION_NO = 0;
    const OTHER_LISTINGS_SYNCHRONIZATION_YES = 1;

    const OTHER_LISTINGS_MAPPING_MODE_NO = 0;
    const OTHER_LISTINGS_MAPPING_MODE_YES = 1;

    const OTHER_LISTINGS_MAPPING_TITLE_MODE_NONE = 0;
    const OTHER_LISTINGS_MAPPING_TITLE_MODE_DEFAULT = 1;
    const OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE = 2;

    const OTHER_LISTINGS_MAPPING_SKU_MODE_NONE = 0;
    const OTHER_LISTINGS_MAPPING_SKU_MODE_DEFAULT = 1;
    const OTHER_LISTINGS_MAPPING_SKU_MODE_PRODUCT_ID = 2;
    const OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE = 3;

    const OTHER_LISTINGS_MAPPING_ITEM_ID_MODE_NONE = 0;
    const OTHER_LISTINGS_MAPPING_ITEM_ID_MODE_CUSTOM_ATTRIBUTE = 1;

    const OTHER_LISTINGS_MAPPING_SKU_DEFAULT_PRIORITY = 1;
    const OTHER_LISTINGS_MAPPING_TITLE_DEFAULT_PRIORITY = 2;
    const OTHER_LISTINGS_MAPPING_ITEM_ID_DEFAULT_PRIORITY = 3;

    const MAGENTO_ORDERS_LISTINGS_MODE_NO = 0;
    const MAGENTO_ORDERS_LISTINGS_MODE_YES = 1;

    const MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT = 0;
    const MAGENTO_ORDERS_LISTINGS_STORE_MODE_CUSTOM = 1;

    const MAGENTO_ORDERS_LISTINGS_OTHER_MODE_NO = 0;
    const MAGENTO_ORDERS_LISTINGS_OTHER_MODE_YES = 1;

    const MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE = 0;
    const MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT = 1;

    const MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO = 'magento';
    const MAGENTO_ORDERS_NUMBER_SOURCE_CHANNEL = 'channel';

    const MAGENTO_ORDERS_NUMBER_PREFIX_MODE_NO = 0;
    const MAGENTO_ORDERS_NUMBER_PREFIX_MODE_YES = 1;

    const MAGENTO_ORDERS_CREATE_IMMEDIATELY = 1;
    const MAGENTO_ORDERS_CREATE_CHECKOUT = 2;
    const MAGENTO_ORDERS_CREATE_PAID = 3;
    const MAGENTO_ORDERS_CREATE_CHECKOUT_AND_PAID = 4;

    const MAGENTO_ORDERS_TAX_MODE_NONE = 0;
    const MAGENTO_ORDERS_TAX_MODE_CHANNEL = 1;
    const MAGENTO_ORDERS_TAX_MODE_MAGENTO = 2;
    const MAGENTO_ORDERS_TAX_MODE_MIXED = 3;

    const MAGENTO_ORDERS_CUSTOMER_MODE_GUEST = 0;
    const MAGENTO_ORDERS_CUSTOMER_MODE_PREDEFINED = 1;
    const MAGENTO_ORDERS_CUSTOMER_MODE_NEW = 2;

    const MAGENTO_ORDERS_CUSTOMER_NEW_SUBSCRIPTION_MODE_NO = 0;
    const MAGENTO_ORDERS_CUSTOMER_NEW_SUBSCRIPTION_MODE_YES = 1;

    const MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT = 0;
    const MAGENTO_ORDERS_STATUS_MAPPING_MODE_CUSTOM = 1;

    const MAGENTO_ORDERS_STATUS_MAPPING_NEW = 'pending';
    const MAGENTO_ORDERS_STATUS_MAPPING_PAID = 'processing';
    const MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED = 'complete';

    const MAGENTO_ORDERS_INVOICE_MODE_NO = 0;
    const MAGENTO_ORDERS_INVOICE_MODE_YES = 1;

    const MAGENTO_ORDERS_SHIPMENT_MODE_NO = 0;
    const MAGENTO_ORDERS_SHIPMENT_MODE_YES = 1;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Account');
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('account');
        return parent::save();
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $storeCategoriesTable = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_account_store_category');

        $this->getResource()->getConnection()
            ->delete($storeCategoriesTable, ['account_id = ?' => $this->getId()]);

        $otherCategoryTemplates = $this->getOtherCategoryTemplates(true);
        foreach ($otherCategoryTemplates as $otherCategoryTemplate) {
            $otherCategoryTemplate->delete();
        }

        $feedbacks = $this->getFeedbacks(true);
        foreach ($feedbacks as $feedback) {
            $feedback->delete();
        }

        $feedbackTemplates = $this->getFeedbackTemplates(true);
        foreach ($feedbackTemplates as $feedbackTemplate) {
            $feedbackTemplate->delete();
        }

        $items = $this->getEbayItems(true);
        foreach ($items as $item) {
            $item->delete();
        }

        $pickupStoreCollection = $this->activeRecordFactory->getObject('Ebay_Account_PickupStore')
            ->getCollection()->addFieldToFilter('account_id', $this->getId());
        foreach ($pickupStoreCollection as $pickupStore) {
            /** @var \Ess\M2ePro\Model\Ebay\Account\PickupStore $pickupStore */
            $pickupStore->delete();
        }

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('account');
        return parent::delete();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\ActiveRecord\AbstractModel[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOtherCategoryTemplates($asObjects = false, array $filters = [])
    {
        return $this->getRelatedSimpleItems('Ebay_Template_OtherCategory', 'account_id', $asObjects, $filters);
    }

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\ActiveRecord\AbstractModel[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getFeedbacks($asObjects = false, array $filters = [])
    {
        return $this->getRelatedSimpleItems('Ebay\Feedback', 'account_id', $asObjects, $filters);
    }

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\ActiveRecord\AbstractModel[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getFeedbackTemplates($asObjects = false, array $filters = [])
    {
        return $this->getRelatedSimpleItems('Ebay_Feedback_Template', 'account_id', $asObjects, $filters);
    }

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\ActiveRecord\AbstractModel[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getEbayItems($asObjects = false, array $filters = [])
    {
        return $this->getRelatedSimpleItems('Ebay\Item', 'account_id', $asObjects, $filters);
    }

    //########################################

    /**
     * @return bool
     */
    public function hasFeedbackTemplate()
    {
        return (bool)$this->activeRecordFactory->getObject('Ebay_Feedback_Template')->getCollection()
            ->addFieldToFilter('account_id', $this->getId())
            ->getSize();
    }

    //########################################

    /**
     * @return int
     */
    public function getMode()
    {
        return (int)$this->getData('mode');
    }

    public function getServerHash()
    {
        return $this->getData('server_hash');
    }

    public function getUserId()
    {
        return $this->getData('user_id');
    }

    public function getTranslationHash()
    {
        return $this->getData('translation_hash');
    }

    /**
     * @return bool
     */
    public function isModeProduction()
    {
        return $this->getMode() == self::MODE_PRODUCTION;
    }

    /**
     * @return bool
     */
    public function isModeSandbox()
    {
        return $this->getMode() == self::MODE_SANDBOX;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getFeedbacksReceive()
    {
        return (int)$this->getData('feedbacks_receive');
    }

    /**
     * @return bool
     */
    public function isFeedbacksReceive()
    {
        return $this->getFeedbacksReceive() == self::FEEDBACKS_RECEIVE_YES;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getFeedbacksAutoResponse()
    {
        return (int)$this->getData('feedbacks_auto_response');
    }

    /**
     * @return bool
     */
    public function isFeedbacksAutoResponseDisabled()
    {
        return $this->getFeedbacksAutoResponse() == self::FEEDBACKS_AUTO_RESPONSE_NONE;
    }

    /**
     * @return bool
     */
    public function isFeedbacksAutoResponseCycled()
    {
        return $this->getFeedbacksAutoResponse() == self::FEEDBACKS_AUTO_RESPONSE_CYCLED;
    }

    /**
     * @return bool
     */
    public function isFeedbacksAutoResponseRandom()
    {
        return $this->getFeedbacksAutoResponse() == self::FEEDBACKS_AUTO_RESPONSE_RANDOM;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getFeedbacksAutoResponseOnlyPositive()
    {
        return (int)$this->getData('feedbacks_auto_response_only_positive');
    }

    /**
     * @return bool
     */
    public function isFeedbacksAutoResponseOnlyPositive()
    {
        return $this->getFeedbacksAutoResponseOnlyPositive() == self::FEEDBACKS_AUTO_RESPONSE_ONLY_POSITIVE_YES;
    }

    //########################################

    /**
     * @return int
     */
    public function getOtherListingsSynchronization()
    {
        return (int)$this->getData('other_listings_synchronization');
    }

    /**
     * @return int
     */
    public function getOtherListingsMappingMode()
    {
        return (int)$this->getData('other_listings_mapping_mode');
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOtherListingsMappingSettings()
    {
        return $this->getSettings('other_listings_mapping_settings');
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getOtherListingsMappingSkuMode()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['sku', 'mode'],
            self::OTHER_LISTINGS_MAPPING_SKU_MODE_NONE
        );

        return (int)$setting;
    }

    /**
     * @return int
     */
    public function getOtherListingsMappingSkuPriority()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['sku', 'priority'],
            self::OTHER_LISTINGS_MAPPING_SKU_DEFAULT_PRIORITY
        );

        return (int)$setting;
    }

    public function getOtherListingsMappingSkuAttribute()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['sku', 'attribute']
        );

        return $setting;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getOtherListingsMappingTitleMode()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['title', 'mode'],
            self::OTHER_LISTINGS_MAPPING_TITLE_MODE_NONE
        );

        return (int)$setting;
    }

    /**
     * @return int
     */
    public function getOtherListingsMappingTitlePriority()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['title', 'priority'],
            self::OTHER_LISTINGS_MAPPING_TITLE_DEFAULT_PRIORITY
        );

        return (int)$setting;
    }

    public function getOtherListingsMappingTitleAttribute()
    {
        $setting = $this->getSetting('other_listings_mapping_settings', ['title', 'attribute']);

        return $setting;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getOtherListingsMappingItemIdMode()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['item_id', 'mode'],
            self::OTHER_LISTINGS_MAPPING_ITEM_ID_MODE_NONE
        );

        return (int)$setting;
    }

    /**
     * @return int
     */
    public function getOtherListingsMappingItemIdPriority()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['item_id', 'priority'],
            self::OTHER_LISTINGS_MAPPING_ITEM_ID_DEFAULT_PRIORITY
        );

        return (int)$setting;
    }

    public function getOtherListingsMappingItemIdAttribute()
    {
        $setting = $this->getSetting('other_listings_mapping_settings', ['item_id', 'attribute']);

        return $setting;
    }

    //########################################

    /**
     * @return bool
     */
    public function isOtherListingsSynchronizationEnabled()
    {
        return $this->getOtherListingsSynchronization() == self::OTHER_LISTINGS_SYNCHRONIZATION_YES;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingEnabled()
    {
        return $this->getOtherListingsMappingMode() == self::OTHER_LISTINGS_MAPPING_MODE_YES;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isOtherListingsMappingSkuModeNone()
    {
        return $this->getOtherListingsMappingSkuMode() == self::OTHER_LISTINGS_MAPPING_SKU_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingSkuModeDefault()
    {
        return $this->getOtherListingsMappingSkuMode() == self::OTHER_LISTINGS_MAPPING_SKU_MODE_DEFAULT;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingSkuModeCustomAttribute()
    {
        return $this->getOtherListingsMappingSkuMode() == self::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingSkuModeProductId()
    {
        return $this->getOtherListingsMappingSkuMode() == self::OTHER_LISTINGS_MAPPING_SKU_MODE_PRODUCT_ID;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isOtherListingsMappingTitleModeNone()
    {
        return $this->getOtherListingsMappingTitleMode() == self::OTHER_LISTINGS_MAPPING_TITLE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingTitleModeDefault()
    {
        return $this->getOtherListingsMappingTitleMode() == self::OTHER_LISTINGS_MAPPING_TITLE_MODE_DEFAULT;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingTitleModeCustomAttribute()
    {
        return $this->getOtherListingsMappingTitleMode() == self::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isOtherListingsMappingItemIdModeNone()
    {
        return $this->getOtherListingsMappingItemIdMode() == self::OTHER_LISTINGS_MAPPING_ITEM_ID_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingItemIdModeCustomAttribute()
    {
        return $this->getOtherListingsMappingItemIdMode() == self::OTHER_LISTINGS_MAPPING_ITEM_ID_MODE_CUSTOM_ATTRIBUTE;
    }

    //########################################

    /**
     * @param int $marketplaceId
     * @return int
     */
    public function getRelatedStoreId($marketplaceId)
    {
        $storeId = $this->getSetting('marketplaces_data', [(int)$marketplaceId, 'related_store_id']);
        return $storeId !== null ? (int)$storeId : \Magento\Store\Model\Store::DEFAULT_STORE_ID;
    }

    //########################################

    /**
     * @return bool
     */
    public function isMagentoOrdersListingsModeEnabled()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['listing', 'mode'],
            self::MAGENTO_ORDERS_LISTINGS_MODE_YES
        );

        return $setting == self::MAGENTO_ORDERS_LISTINGS_MODE_YES;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersListingsStoreCustom()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['listing', 'store_mode'],
            self::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT
        );

        return $setting == self::MAGENTO_ORDERS_LISTINGS_STORE_MODE_CUSTOM;
    }

    /**
     * @return int
     */
    public function getMagentoOrdersListingsStoreId()
    {
        $setting = $this->getSetting('magento_orders_settings', ['listing', 'store_id'], 0);

        return (int)$setting;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isMagentoOrdersListingsOtherModeEnabled()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['listing_other', 'mode'],
            self::MAGENTO_ORDERS_LISTINGS_OTHER_MODE_YES
        );

        return $setting == self::MAGENTO_ORDERS_LISTINGS_OTHER_MODE_YES;
    }

    /**
     * @return int
     */
    public function getMagentoOrdersListingsOtherStoreId()
    {
        $setting = $this->getSetting('magento_orders_settings', ['listing_other', 'store_id'], 0);

        return (int)$setting;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersListingsOtherProductImportEnabled()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['listing_other', 'product_mode'],
            self::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT
        );

        return $setting == self::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT;
    }

    /**
     * @return int
     */
    public function getMagentoOrdersListingsOtherProductTaxClassId()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['listing_other', 'product_tax_class_id'],
            \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE
        );

        return (int)$setting;
    }

    // ---------------------------------------

    public function getMagentoOrdersNumberSource()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['number', 'source'],
            self::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO
        );
        return $setting;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersNumberSourceMagento()
    {
        return $this->getMagentoOrdersNumberSource() == self::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersNumberSourceChannel()
    {
        return $this->getMagentoOrdersNumberSource() == self::MAGENTO_ORDERS_NUMBER_SOURCE_CHANNEL;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isMagentoOrdersNumberPrefixEnable()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['number', 'prefix', 'mode'],
            self::MAGENTO_ORDERS_NUMBER_PREFIX_MODE_NO
        );
        return $setting == self::MAGENTO_ORDERS_NUMBER_PREFIX_MODE_YES;
    }

    public function getMagentoOrdersNumberRegularPrefix()
    {
        $settings = $this->getSetting('magento_orders_settings', ['number', 'prefix']);
        return isset($settings['prefix']) ? $settings['prefix'] : '';
    }

    // ---------------------------------------

    public function getMagentoOrdersCreationMode()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['creation', 'mode'],
            self::MAGENTO_ORDERS_CREATE_CHECKOUT_AND_PAID
        );

        return $setting;
    }

    /**
     * @return bool
     */
    public function shouldCreateMagentoOrderImmediately()
    {
        return $this->getMagentoOrdersCreationMode() == self::MAGENTO_ORDERS_CREATE_IMMEDIATELY;
    }

    /**
     * @return bool
     */
    public function shouldCreateMagentoOrderWhenCheckedOut()
    {
        return $this->getMagentoOrdersCreationMode() == self::MAGENTO_ORDERS_CREATE_CHECKOUT;
    }

    /**
     * @return bool
     */
    public function shouldCreateMagentoOrderWhenPaid()
    {
        return $this->getMagentoOrdersCreationMode() == self::MAGENTO_ORDERS_CREATE_PAID;
    }

    /**
     * @return bool
     */
    public function shouldCreateMagentoOrderWhenCheckedOutAndPaid()
    {
        return $this->getMagentoOrdersCreationMode() == self::MAGENTO_ORDERS_CREATE_CHECKOUT_AND_PAID;
    }

    /**
     * @return int
     */
    public function getMagentoOrdersReservationDays()
    {
        $setting = $this->getSetting('magento_orders_settings', ['creation', 'reservation_days'], 0);

        return (int)$setting;
    }

    /**
     * @return int
     */
    public function getQtyReservationDays()
    {
        $setting = $this->getSetting('magento_orders_settings', ['qty_reservation', 'days'], 1);

        return (int)$setting;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isMagentoOrdersTaxModeNone()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['tax', 'mode'],
            self::MAGENTO_ORDERS_TAX_MODE_MIXED
        );

        return $setting == self::MAGENTO_ORDERS_TAX_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersTaxModeChannel()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['tax', 'mode'],
            self::MAGENTO_ORDERS_TAX_MODE_MIXED
        );

        return $setting == self::MAGENTO_ORDERS_TAX_MODE_CHANNEL;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersTaxModeMagento()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['tax', 'mode'],
            self::MAGENTO_ORDERS_TAX_MODE_MIXED
        );

        return $setting == self::MAGENTO_ORDERS_TAX_MODE_MAGENTO;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersTaxModeMixed()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['tax', 'mode'],
            self::MAGENTO_ORDERS_TAX_MODE_MIXED
        );

        return $setting == self::MAGENTO_ORDERS_TAX_MODE_MIXED;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isMagentoOrdersCustomerGuest()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['customer', 'mode'],
            self::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST
        );

        return $setting == self::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersCustomerPredefined()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['customer', 'mode'],
            self::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST
        );

        return $setting == self::MAGENTO_ORDERS_CUSTOMER_MODE_PREDEFINED;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersCustomerNew()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['customer', 'mode'],
            self::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST
        );

        return $setting == self::MAGENTO_ORDERS_CUSTOMER_MODE_NEW;
    }

    /**
     * @return int
     */
    public function getMagentoOrdersCustomerId()
    {
        $setting = $this->getSetting('magento_orders_settings', ['customer', 'id']);

        return (int)$setting;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersCustomerNewSubscribed()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['customer', 'subscription_mode'],
            self::MAGENTO_ORDERS_CUSTOMER_NEW_SUBSCRIPTION_MODE_NO
        );

        return $setting == self::MAGENTO_ORDERS_CUSTOMER_NEW_SUBSCRIPTION_MODE_YES;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersCustomerNewNotifyWhenCreated()
    {
        $setting = $this->getSetting('magento_orders_settings', ['customer', 'notifications', 'customer_created']);

        return (bool)$setting;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersCustomerNewNotifyWhenOrderCreated()
    {
        $setting = $this->getSetting('magento_orders_settings', ['customer', 'notifications', 'order_created']);

        return (bool)$setting;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersCustomerNewNotifyWhenInvoiceCreated()
    {
        $setting = $this->getSetting('magento_orders_settings', ['customer', 'notifications', 'invoice_created']);

        return (bool)$setting;
    }

    /**
     * @return int
     */
    public function getMagentoOrdersCustomerNewWebsiteId()
    {
        $setting = $this->getSetting('magento_orders_settings', ['customer', 'website_id']);

        return (int)$setting;
    }

    /**
     * @return int
     */
    public function getMagentoOrdersCustomerNewGroupId()
    {
        $setting = $this->getSetting('magento_orders_settings', ['customer', 'group_id']);

        return (int)$setting;
    }

    // ---------------------------------------

    public function isMagentoOrdersInStorePickupEnabled()
    {
        $setting = $this->getSetting('magento_orders_settings', ['in_store_pickup_statues', 'mode'], 0);
        return (bool)$setting;
    }

    // ---------------------------------------

    public function getMagentoOrdersInStorePickupStatusReadyForPickup()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['in_store_pickup_statues', 'ready_for_pickup'],
            null
        );

        return $setting;
    }

    public function getMagentoOrdersInStorePickupStatusPickedUp()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['in_store_pickup_statues', 'picked_up'],
            null
        );

        return $setting;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isMagentoOrdersStatusMappingDefault()
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['status_mapping', 'mode'],
            self::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT
        );

        return $setting == self::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT;
    }

    // ---------------------------------------

    public function getMagentoOrdersStatusNew()
    {
        if ($this->isMagentoOrdersStatusMappingDefault()) {
            return self::MAGENTO_ORDERS_STATUS_MAPPING_NEW;
        }

        return $this->getSetting('magento_orders_settings', ['status_mapping', 'new']);
    }

    public function getMagentoOrdersStatusPaid()
    {
        if ($this->isMagentoOrdersStatusMappingDefault()) {
            return self::MAGENTO_ORDERS_STATUS_MAPPING_PAID;
        }

        return $this->getSetting('magento_orders_settings', ['status_mapping', 'paid']);
    }

    public function getMagentoOrdersStatusShipped()
    {
        if ($this->isMagentoOrdersStatusMappingDefault()) {
            return self::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED;
        }

        return $this->getSetting('magento_orders_settings', ['status_mapping', 'shipped']);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isMagentoOrdersInvoiceEnabled()
    {
        if ($this->isMagentoOrdersStatusMappingDefault()) {
            return true;
        }

        return $this->getSetting('magento_orders_settings', 'invoice_mode') == self::MAGENTO_ORDERS_INVOICE_MODE_YES;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isMagentoOrdersShipmentEnabled()
    {
        if ($this->isMagentoOrdersStatusMappingDefault()) {
            return true;
        }

        return $this->getSetting('magento_orders_settings', 'shipment_mode') == self::MAGENTO_ORDERS_SHIPMENT_MODE_YES;
    }

    //########################################

    /**
     * @return array
     * @throws Logic
     */
    public function getUserPreferences()
    {
        return $this->getSettings('user_preferences');
    }

    public function updateUserPreferences()
    {
        $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector(
            'account',
            'get',
            'userPreferences',
            [],
            null,
            null,
            $this->getId()
        );

        $dispatcherObject->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        if (empty($responseData['user_preferences'])) {
            return;
        }

        $this->setData(
            'user_preferences',
            $this->getHelper('Data')->jsonEncode($responseData['user_preferences'])
        )->save();
    }

    // ---------------------------------------

    /**
     * @param bool $returnRealValue
     * @return bool|null
     */
    public function getOutOfStockControl($returnRealValue = false)
    {
        $userPreferences = $this->getUserPreferences();

        if (isset($userPreferences['OutOfStockControlPreference'])) {
            return strtolower($userPreferences['OutOfStockControlPreference']) === 'true';
        }

        return $returnRealValue ? null : false;
    }

    //########################################

    public function isPickupStoreEnabled()
    {
        $additionalData = $this->getHelper('Data')->jsonDecode($this->getParentObject()->getData('additional_data'));
        return $this->getHelper('Component_Ebay_PickupStore')->isFeatureEnabled() && !empty($additionalData['bopis']);
    }

    //########################################

    public function getTokenSession()
    {
        return $this->getData('token_session');
    }

    public function getTokenExpiredDate()
    {
        return $this->getData('token_expired_date');
    }

    // ---------------------------------------

    public function getFeedbacksLastUsedId()
    {
        return $this->getData('feedbacks_last_used_id');
    }

    // ---------------------------------------

    public function getEbayStoreTitle()
    {
        return $this->getData('ebay_store_title');
    }

    public function getEbayStoreUrl()
    {
        return $this->getData('ebay_store_url');
    }

    public function getEbayStoreSubscriptionLevel()
    {
        return $this->getData('ebay_store_subscription_level');
    }

    public function getEbayStoreDescription()
    {
        return $this->getData('ebay_store_description');
    }

    public function getEbayStoreCategory($id)
    {
        $connection = $this->getResource()->getConnection();

        $tableAccountStoreCategories = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_account_store_category');

        $dbSelect = $connection->select()
            ->from($tableAccountStoreCategories, '*')
            ->where('`account_id` = ?', (int)$this->getId())
            ->where('`category_id` = ?', (int)$id)
            ->order(['sorder ASC']);

        $categories = $connection->fetchAll($dbSelect);

        return !empty($categories) ? $categories[0] : [];
    }

    /**
     * @return array
     */
    public function getEbayStoreCategories()
    {
        $tableAccountStoreCategories = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_account_store_category');

        $connRead = $this->getResource()->getConnection();

        $dbSelect = $connRead->select()
                             ->from($tableAccountStoreCategories, '*')
                             ->where('`account_id` = ?', (int)$this->getId())
                             ->order(['sorder ASC']);

        return $connRead->fetchAll($dbSelect);
    }

    public function buildEbayStoreCategoriesTreeRec($data, $rootId)
    {
        $children = [];

        foreach ($data as $node) {
            if ($node['parent_id'] == $rootId) {
                $children[] = [
                    'id' => $node['category_id'],
                    'text' => $node['title'],
                    'allowDrop' => false,
                    'allowDrag' => false,
                    'children' => []
                ];
            }
        }

        foreach ($children as &$child) {
            $child['children'] = $this->buildEbayStoreCategoriesTreeRec($data, $child['id']);
        }

        return $children;
    }

    public function buildEbayStoreCategoriesTree()
    {
        return $this->buildEbayStoreCategoriesTreeRec($this->getEbayStoreCategories(), 0);
    }

    // ---------------------------------------

    public function updateEbayStoreInfo()
    {
        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'account',
            'get',
            'store',
            [],
            null,
            null,
            $this->getId()
        );

        $dispatcherObj->process($connectorObj);
        $data = $connectorObj->getResponseData();

        if (!is_array($data)) {
            return;
        }

        $infoKeys = [
            'title',
            'url',
            'subscription_level',
            'description',
        ];

        $dataForUpdate = [];
        foreach ($infoKeys as $key) {
            if (!isset($data['data'][$key])) {
                $dataForUpdate['ebay_store_'.$key] = '';
                continue;
            }
            $dataForUpdate['ebay_store_'.$key] = $data['data'][$key];
        }
        $this->addData($dataForUpdate);
        $this->save();

        $connection = $this->getResource()->getConnection();

        $tableAccountStoreCategories = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_ebay_account_store_category');

        $connection->delete($tableAccountStoreCategories, ['account_id = ?'=>$this->getId()]);

        if (empty($data['categories'])) {
            return;
        }

        foreach ($data['categories'] as &$item) {
            $item['account_id'] = $this->getId();
            $connection->insertOnDuplicate($tableAccountStoreCategories, $item);
        }
    }

    //########################################

    public function updateShippingDiscountProfiles($marketplaceId)
    {
        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'account',
            'get',
            'shippingDiscountProfiles',
            [],
            null,
            $marketplaceId,
            $this->getId(),
            null
        );

        $dispatcherObj->process($connectorObj);
        $data = $connectorObj->getResponseData();

        if (empty($data)) {
            return;
        }

        if ($this->getData('ebay_shipping_discount_profiles') === null) {
            $profiles = [];
        } else {
            $profiles = $this->getHelper('Data')->jsonDecode($this->getData('ebay_shipping_discount_profiles'));
        }

        $profiles[$marketplaceId] = $data;

        $this->setData(
            'ebay_shipping_discount_profiles',
            $this->getHelper('Data')->jsonEncode($profiles)
        )->save();
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
