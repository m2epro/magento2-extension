<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Account getParentObject()
 */

namespace Ess\M2ePro\Model\Walmart;

/**
 * Class \Ess\M2ePro\Model\Walmart\Account
 */
class Account extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
    const OTHER_LISTINGS_SYNCHRONIZATION_NO = 0;
    const OTHER_LISTINGS_SYNCHRONIZATION_YES = 1;

    const OTHER_LISTINGS_MAPPING_MODE_NO = 0;
    const OTHER_LISTINGS_MAPPING_MODE_YES = 1;

    const OTHER_LISTINGS_MAPPING_SKU_MODE_NONE = 0;
    const OTHER_LISTINGS_MAPPING_SKU_MODE_DEFAULT = 1;
    const OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE = 2;
    const OTHER_LISTINGS_MAPPING_SKU_MODE_PRODUCT_ID = 3;

    const OTHER_LISTINGS_MAPPING_UPC_MODE_NONE = 0;
    const OTHER_LISTINGS_MAPPING_UPC_MODE_CUSTOM_ATTRIBUTE = 2;

    const OTHER_LISTINGS_MAPPING_GTIN_MODE_NONE = 0;
    const OTHER_LISTINGS_MAPPING_GTIN_MODE_CUSTOM_ATTRIBUTE = 2;

    const OTHER_LISTINGS_MAPPING_WPID_MODE_NONE = 0;
    const OTHER_LISTINGS_MAPPING_WPID_MODE_CUSTOM_ATTRIBUTE = 2;

    const OTHER_LISTINGS_MAPPING_TITLE_MODE_NONE = 0;
    const OTHER_LISTINGS_MAPPING_TITLE_MODE_DEFAULT = 1;
    const OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE = 2;

    const OTHER_LISTINGS_MAPPING_SKU_DEFAULT_PRIORITY = 1;
    const OTHER_LISTINGS_MAPPING_UPC_DEFAULT_PRIORITY = 2;
    const OTHER_LISTINGS_MAPPING_GTIN_DEFAULT_PRIORITY = 3;
    const OTHER_LISTINGS_MAPPING_WPID_DEFAULT_PRIORITY = 4;
    const OTHER_LISTINGS_MAPPING_TITLE_DEFAULT_PRIORITY = 5;

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
    const MAGENTO_ORDERS_STATUS_MAPPING_PROCESSING = 'processing';
    const MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED = 'complete';

    const MAGENTO_ORDERS_INVOICE_MODE_NO = 0;
    const MAGENTO_ORDERS_INVOICE_MODE_YES = 1;

    const MAGENTO_ORDERS_SHIPMENT_MODE_NO = 0;
    const MAGENTO_ORDERS_SHIPMENT_MODE_YES = 1;

    //########################################

    /**
     * @var \Ess\M2ePro\Model\Marketplace
     */
    private $marketplaceModel = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Account');
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $items = $this->getWalmartItems(true);
        foreach ($items as $item) {
            $item->delete();
        }

        $this->marketplaceModel = null;

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('account');
        return parent::delete();
    }

    //########################################

    public function getWalmartItems($asObjects = false, array $filters = [])
    {
        return $this->getRelatedSimpleItems('Walmart\Item', 'account_id', $asObjects, $filters);
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        if ($this->marketplaceModel === null) {
            $this->marketplaceModel = $this->walmartFactory->getCachedObjectLoaded(
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

    public function getServerHash()
    {
        return $this->getData('server_hash');
    }

    /**
     * @return int
     */
    public function getMarketplaceId()
    {
        return (int)$this->getData('marketplace_id');
    }

    public function getConsumerId()
    {
        return $this->getData('consumer_id');
    }

    public function getClientId()
    {
        return $this->getData('client_id');
    }

    public function getClientSecret()
    {
        return $this->getData('client_secret');
    }

    public function getOldPrivateKey()
    {
        return $this->getData('old_private_key');
    }

    /**
     * @return int
     */
    public function getRelatedStoreId()
    {
        return (int)$this->getData('related_store_id');
    }

    // ---------------------------------------

    public function getInfo()
    {
        return $this->getData('info');
    }

    /**
     * @return array|null
     */
    public function getDecodedInfo()
    {
        $tempInfo = $this->getInfo();
        return $tempInfo === null ? null : $this->getHelper('Data')->jsonDecode($tempInfo);
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
    public function getOtherListingsMappingUpcMode()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['upc', 'mode'],
            self::OTHER_LISTINGS_MAPPING_UPC_MODE_NONE
        );

        return (int)$setting;
    }

    /**
     * @return int
     */
    public function getOtherListingsMappingUpcPriority()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['upc', 'priority'],
            self::OTHER_LISTINGS_MAPPING_UPC_DEFAULT_PRIORITY
        );

        return (int)$setting;
    }

    public function getOtherListingsMappingUpcAttribute()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['upc', 'attribute']
        );

        return $setting;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getOtherListingsMappingGtinMode()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['gtin', 'mode'],
            self::OTHER_LISTINGS_MAPPING_GTIN_MODE_NONE
        );

        return (int)$setting;
    }

    /**
     * @return int
     */
    public function getOtherListingsMappingGtinPriority()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['gtin', 'priority'],
            self::OTHER_LISTINGS_MAPPING_GTIN_DEFAULT_PRIORITY
        );

        return (int)$setting;
    }

    public function getOtherListingsMappingGtinAttribute()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['gtin', 'attribute']
        );

        return $setting;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getOtherListingsMappingWpidMode()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['wpid', 'mode'],
            self::OTHER_LISTINGS_MAPPING_WPID_MODE_NONE
        );

        return (int)$setting;
    }

    /**
     * @return int
     */
    public function getOtherListingsMappingWpidPriority()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['wpid', 'priority'],
            self::OTHER_LISTINGS_MAPPING_WPID_DEFAULT_PRIORITY
        );

        return (int)$setting;
    }

    public function getOtherListingsMappingWpidAttribute()
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['wpid', 'attribute']
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
    public function isOtherListingsMappingGtinModeNone()
    {
        return $this->getOtherListingsMappingGtinMode() == self::OTHER_LISTINGS_MAPPING_GTIN_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingGtinModeCustomAttribute()
    {
        return $this->getOtherListingsMappingGtinMode() == self::OTHER_LISTINGS_MAPPING_GTIN_MODE_CUSTOM_ATTRIBUTE;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isOtherListingsMappingWpidModeNone()
    {
        return $this->getOtherListingsMappingWpidMode() == self::OTHER_LISTINGS_MAPPING_WPID_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingWpidModeCustomAttribute()
    {
        return $this->getOtherListingsMappingWpidMode() == self::OTHER_LISTINGS_MAPPING_WPID_MODE_CUSTOM_ATTRIBUTE;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isOtherListingsMappingUpcModeNone()
    {
        return $this->getOtherListingsMappingUpcMode() == self::OTHER_LISTINGS_MAPPING_UPC_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingUpcModeCustomAttribute()
    {
        return $this->getOtherListingsMappingUpcMode() == self::OTHER_LISTINGS_MAPPING_UPC_MODE_CUSTOM_ATTRIBUTE;
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
        $setting = $this->getSetting('magento_orders_settings', ['listing_other', 'product_tax_class_id']);

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

    /**
     * @return int
     */
    public function getQtyReservationDays()
    {
        $setting = $this->getSetting('magento_orders_settings', ['qty_reservation', 'days']);

        return (int)$setting;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isMagentoOrdersTaxModeNone()
    {
        $setting = $this->getSetting('magento_orders_settings', ['tax', 'mode']);

        return $setting == self::MAGENTO_ORDERS_TAX_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersTaxModeChannel()
    {
        $setting = $this->getSetting('magento_orders_settings', ['tax', 'mode']);

        return $setting == self::MAGENTO_ORDERS_TAX_MODE_CHANNEL;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersTaxModeMagento()
    {
        $setting = $this->getSetting('magento_orders_settings', ['tax', 'mode']);

        return $setting == self::MAGENTO_ORDERS_TAX_MODE_MAGENTO;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersTaxModeMixed()
    {
        $setting = $this->getSetting('magento_orders_settings', ['tax', 'mode']);

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

    public function getMagentoOrdersStatusProcessing()
    {
        if ($this->isMagentoOrdersStatusMappingDefault()) {
            return self::MAGENTO_ORDERS_STATUS_MAPPING_PROCESSING;
        }

        return $this->getSetting('magento_orders_settings', ['status_mapping', 'processing']);
    }

    public function getMagentoOrdersStatusShipped()
    {
        if ($this->isMagentoOrdersStatusMappingDefault()) {
            return self::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED;
        }

        return $this->getSetting('magento_orders_settings', ['status_mapping', 'shipped']);
    }

    // ---------------------------------------

    public function isMagentoOrdersInvoiceEnabled()
    {
        if ($this->isMagentoOrdersStatusMappingDefault()) {
            return true;
        }

        return $this->getSetting('magento_orders_settings', 'invoice_mode') == self::MAGENTO_ORDERS_INVOICE_MODE_YES;
    }

    public function isMagentoOrdersShipmentEnabled()
    {
        if ($this->isMagentoOrdersStatusMappingDefault()) {
            return true;
        }

        return $this->getSetting('magento_orders_settings', 'shipment_mode') == self::MAGENTO_ORDERS_SHIPMENT_MODE_YES;
    }

    //########################################

    /**
     * @return bool
     */
    public function isVatCalculationServiceEnabled()
    {
        return (bool)$this->getData('is_vat_calculation_service_enabled');
    }

    /**
     * @return bool
     */
    public function isMagentoInvoiceCreationDisabled()
    {
        return (bool)$this->getData('is_magento_invoice_creation_disabled');
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
