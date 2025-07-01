<?php

namespace Ess\M2ePro\Model\Amazon;

use Ess\M2ePro\Model\ResourceModel\Amazon\Account as ResourceAccount;

class Account extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    public const OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_NONE = 0;
    public const OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_CUSTOM_ATTRIBUTE = 1;

    public const OTHER_LISTINGS_MAPPING_SKU_MODE_NONE = 0;
    public const OTHER_LISTINGS_MAPPING_SKU_MODE_DEFAULT = 1;
    public const OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE = 2;
    public const OTHER_LISTINGS_MAPPING_SKU_MODE_PRODUCT_ID = 3;

    public const OTHER_LISTINGS_MAPPING_TITLE_MODE_NONE = 0;
    public const OTHER_LISTINGS_MAPPING_TITLE_MODE_DEFAULT = 1;
    public const OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE = 2;

    public const OTHER_LISTINGS_MAPPING_SKU_DEFAULT_PRIORITY = 1;
    public const OTHER_LISTINGS_MAPPING_GENERAL_ID_DEFAULT_PRIORITY = 2;
    public const OTHER_LISTINGS_MAPPING_TITLE_DEFAULT_PRIORITY = 3;

    public const MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT = 0;
    public const MAGENTO_ORDERS_LISTINGS_STORE_MODE_CUSTOM = 1;

    public const MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE = 0;
    public const MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IMPORT = 1;

    public const MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO = 'magento';
    public const MAGENTO_ORDERS_NUMBER_SOURCE_CHANNEL = 'channel';

    public const MAGENTO_ORDERS_TAX_MODE_NONE = 0;
    public const MAGENTO_ORDERS_TAX_MODE_CHANNEL = 1;
    public const MAGENTO_ORDERS_TAX_MODE_MAGENTO = 2;
    public const MAGENTO_ORDERS_TAX_MODE_MIXED = 3;

    public const MAGENTO_ORDERS_TAX_ROUND_OF_RATE_YES = 1;
    public const MAGENTO_ORDERS_TAX_ROUND_OF_RATE_NO = 0;

    public const SKIP_TAX_FOR_UK_SHIPMENT_NONE = 0;
    public const SKIP_TAX_FOR_UK_SHIPMENT = 1;
    public const SKIP_TAX_FOR_UK_SHIPMENT_WITH_CERTAIN_PRICE = 2;

    public const MAGENTO_ORDERS_CUSTOMER_MODE_GUEST = 0;
    public const MAGENTO_ORDERS_CUSTOMER_MODE_PREDEFINED = 1;
    public const MAGENTO_ORDERS_CUSTOMER_MODE_NEW = 2;

    public const MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT = 0;
    public const MAGENTO_ORDERS_STATUS_MAPPING_MODE_CUSTOM = 1;

    public const MAGENTO_ORDERS_STATUS_MAPPING_NEW = 'pending';
    public const MAGENTO_ORDERS_STATUS_MAPPING_PROCESSING = 'processing';
    public const MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED = 'complete';

    public const AUTO_INVOICING_DISABLED = 0;
    public const AUTO_INVOICING_VAT_CALCULATION_SERVICE = 1;
    public const AUTO_INVOICING_UPLOAD_MAGENTO_INVOICES = 2;

    public const INVOICE_GENERATION_BY_AMAZON = 1;
    public const INVOICE_GENERATION_BY_EXTENSION = 2;

    /** @var \Ess\M2ePro\Model\Marketplace */
    private $marketplaceModel;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing */
    private $repricingModel;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Permanent */
    private $cachePermanent;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Cache\Permanent $cachePermanent,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
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

        $this->cachePermanent = $cachePermanent;
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\Account::class);
    }

    public function save()
    {
        $this->cachePermanent->removeTagValues('account');

        return parent::save();
    }

    public function getAccountId(): int
    {
        return (int)$this->getDataByKey(ResourceAccount::COLUMN_ACCOUNT_ID);
    }

    public function getAmazonItems($asObjects = false, array $filters = [])
    {
        return $this->getRelatedSimpleItems('Amazon\Item', 'account_id', $asObjects, $filters);
    }

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

    /**
     * @return bool
     */
    public function isRepricing(): bool
    {
        $cacheKey = 'amazon_account_' . $this->getId() . '_is_repricing';
        $cacheData = $this->cachePermanent->getValue($cacheKey);

        if ($cacheData !== null) {
            return (bool)$cacheData;
        }

        $repricingCollection = $this->activeRecordFactory->getObject('Amazon_Account_Repricing')->getCollection();
        $repricingCollection->addFieldToFilter('account_id', $this->getId());
        $isRepricing = (int)(bool)$repricingCollection->getSize();

        $this->cachePermanent->setValue(
            $cacheKey,
            $isRepricing,
            ['account'],
            $this->getCacheLifetime()
        );

        return (bool)$isRepricing;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Account\Repricing
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRepricing(): Account\Repricing
    {
        if ($this->repricingModel === null) {
            $this->repricingModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Amazon_Account_Repricing',
                $this->getId(),
                null
            );
        }

        return $this->repricingModel;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getShippingPolicies(): array
    {
        return $this->getRelatedSimpleItems('Amazon_Template_Shipping', 'account_id', true);
    }

    /**
     * @return void
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function deleteShippingPolicies(): void
    {
        $policies = $this->getShippingPolicies();

        foreach ($policies as $policy) {
            $policy->delete();
        }
    }

    public function getInventorySku(): array
    {
        return $this->getRelatedSimpleItems('Amazon_Inventory_Sku', 'account_id', true);
    }

    public function deleteInventorySku(): void
    {
        $items = $this->getInventorySku();

        foreach ($items as $item) {
            $item->delete();
        }
    }

    public function getProcessingListSku(): array
    {
        return $this->getRelatedSimpleItems('Amazon_Listing_Product_Action_ProcessingListSku', 'account_id', true);
    }

    public function deleteProcessingListSku(): void
    {
        $items = $this->getProcessingListSku();

        foreach ($items as $item) {
            $item->delete();
        }
    }

    public function getDictionaryTemplateShipping($asObjects = false): array
    {
        return $this->getRelatedSimpleItems('Amazon_Dictionary_TemplateShipping', 'account_id', $asObjects);
    }

    public function deleteDictionaryTemplateShipping(): void
    {
        $templates = $this->getDictionaryTemplateShipping(true);

        foreach ($templates as $template) {
            $template->delete();
        }
    }

    public function getServerHash()
    {
        return $this->getData('server_hash');
    }

    /**
     * @return int
     */
    public function getMarketplaceId(): int
    {
        return (int)$this->getData('marketplace_id');
    }

    public function getMerchantId()
    {
        return $this->getData('merchant_id');
    }

    public function getToken()
    {
        return $this->getData('token');
    }

    /**
     * @return int
     */
    public function getRelatedStoreId(): int
    {
        return (int)$this->getData('related_store_id');
    }

    public function getInfo()
    {
        return $this->getData('info');
    }

    /**
     * @return array|null
     */
    public function getDecodedInfo(): ?array
    {
        $tempInfo = $this->getInfo();

        return $tempInfo === null ? null : \Ess\M2ePro\Helper\Json::decode($tempInfo);
    }

    /**
     * @return int
     */
    public function getOtherListingsSynchronization(): int
    {
        return (int)$this->getData('other_listings_synchronization');
    }

    /**
     * @return int
     */
    public function getOtherListingsMappingMode(): int
    {
        return (int)$this->getData('other_listings_mapping_mode');
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOtherListingsMappingSettings(): array
    {
        return $this->getSettings('other_listings_mapping_settings');
    }

    /**
     * @return int
     */
    public function getOtherListingsMappingGeneralIdMode(): int
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['general_id', 'mode'],
            self::OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_NONE
        );

        return (int)$setting;
    }

    public function isImportLabelsToMagentoOrder(): bool
    {
        return (bool)$this->getSetting(
            'magento_orders_settings',
            ['shipping_information', 'import_labels'],
            true
        );
    }

    /**
     * @return bool
     */
    public function isImportShipByDateToMagentoOrder(): bool
    {
        return (bool)$this->getSetting(
            'magento_orders_settings',
            ['shipping_information', 'ship_by_date'],
            true
        );
    }

    /**
     * @return bool
     */
    public function isUpdateWithoutTrackToMagentoOrder(): bool
    {
        return (bool)$this->getSetting(
            'magento_orders_settings',
            ['shipping_information', 'update_without_track'],
            true
        );
    }

    /**
     * @return int
     */
    public function getOtherListingsMappingGeneralIdPriority(): int
    {
        $setting = $this->getSetting(
            'other_listings_mapping_settings',
            ['general_id', 'priority'],
            self::OTHER_LISTINGS_MAPPING_GENERAL_ID_DEFAULT_PRIORITY
        );

        return (int)$setting;
    }

    public function getOtherListingsMappingGeneralIdAttribute()
    {
        return $this->getSetting('other_listings_mapping_settings', ['general_id', 'attribute']);
    }

    /**
     * @return int
     */
    public function getOtherListingsMappingSkuMode(): int
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
    public function getOtherListingsMappingSkuPriority(): int
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
        return $this->getSetting(
            'other_listings_mapping_settings',
            ['sku', 'attribute']
        );
    }

    /**
     * @return int
     */
    public function getOtherListingsMappingTitleMode(): int
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
    public function getOtherListingsMappingTitlePriority(): int
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
        return $this->getSetting('other_listings_mapping_settings', ['title', 'attribute']);
    }

    /**
     * @return bool
     */
    public function isOtherListingsSynchronizationEnabled(): bool
    {
        return $this->getOtherListingsSynchronization() == 1;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingEnabled(): bool
    {
        return $this->getOtherListingsMappingMode() == 1;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingGeneralIdModeNone(): bool
    {
        return $this->getOtherListingsMappingGeneralIdMode() == self::OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingGeneralIdModeCustomAttribute(): bool
    {
        return $this->getOtherListingsMappingGeneralIdMode() ==
            self::OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingSkuModeNone(): bool
    {
        return $this->getOtherListingsMappingSkuMode() == self::OTHER_LISTINGS_MAPPING_SKU_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingSkuModeDefault(): bool
    {
        return $this->getOtherListingsMappingSkuMode() == self::OTHER_LISTINGS_MAPPING_SKU_MODE_DEFAULT;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingSkuModeCustomAttribute(): bool
    {
        return $this->getOtherListingsMappingSkuMode() == self::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingSkuModeProductId(): bool
    {
        return $this->getOtherListingsMappingSkuMode() == self::OTHER_LISTINGS_MAPPING_SKU_MODE_PRODUCT_ID;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingTitleModeNone(): bool
    {
        return $this->getOtherListingsMappingTitleMode() == self::OTHER_LISTINGS_MAPPING_TITLE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingTitleModeDefault(): bool
    {
        return $this->getOtherListingsMappingTitleMode() == self::OTHER_LISTINGS_MAPPING_TITLE_MODE_DEFAULT;
    }

    /**
     * @return bool
     */
    public function isOtherListingsMappingTitleModeCustomAttribute(): bool
    {
        return $this->getOtherListingsMappingTitleMode() == self::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersListingsModeEnabled(): bool
    {
        return $this->getSetting('magento_orders_settings', ['listing', 'mode'], 1) == 1;
    }

    public function getMagentoOrdersListingsCreateFromDateOrAccountCreateDate(): \DateTime
    {
        $date = $this->getMagentoOrdersListingsCreateFromDate();
        if ($date !== null) {
            return $date;
        }

        /** @var \Ess\M2ePro\Model\Account $parentObject */
        $parentObject = $this->getParentObject();

        return $parentObject->getCreateDate();
    }

    public function getMagentoOrdersListingsCreateFromDate(): ?\DateTime
    {
        $date = $this->getSetting('magento_orders_settings', ['listing', 'create_from_date']);
        if (empty($date)) {
            return null;
        }

        return \Ess\M2ePro\Helper\Date::createDateGmt($date);
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersListingsStoreCustom(): bool
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
    public function getMagentoOrdersListingsStoreId(): int
    {
        $setting = $this->getSetting('magento_orders_settings', ['listing', 'store_id'], 0);

        return (int)$setting;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersListingsOtherModeEnabled(): bool
    {
        return $this->getSetting('magento_orders_settings', ['listing_other', 'mode'], 1) == 1;
    }

    public function getMagentoOrdersListingsOtherCreateFromDateOrAccountCreateDate(): \DateTime
    {
        $date = $this->getMagentoOrdersListingsOtherCreateFromDate();
        if ($date !== null) {
            return $date;
        }

        /** @var \Ess\M2ePro\Model\Account $parentObject */
        $parentObject = $this->getParentObject();

        return $parentObject->getCreateDate();
    }

    public function getMagentoOrdersListingsOtherCreateFromDate(): ?\DateTime
    {
        $date = $this->getSetting('magento_orders_settings', ['listing_other', 'create_from_date']);
        if (empty($date)) {
            return null;
        }

        return \Ess\M2ePro\Helper\Date::createDateGmt($date);
    }

    /**
     * @return int
     */
    public function getMagentoOrdersListingsOtherStoreId(): int
    {
        $setting = $this->getSetting('magento_orders_settings', ['listing_other', 'store_id'], 0);

        return (int)$setting;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersListingsOtherProductImportEnabled(): bool
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
    public function getMagentoOrdersListingsOtherProductTaxClassId(): int
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['listing_other', 'product_tax_class_id'],
            \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE
        );

        return (int)$setting;
    }

    public function getMagentoOrdersNumberSource()
    {
        return $this->getSetting(
            'magento_orders_settings',
            ['number', 'source'],
            self::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO
        );
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersNumberSourceMagento(): bool
    {
        return $this->getMagentoOrdersNumberSource() == self::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersNumberSourceChannel(): bool
    {
        return $this->getMagentoOrdersNumberSource() == self::MAGENTO_ORDERS_NUMBER_SOURCE_CHANNEL;
    }

    /**
     * @return string
     */
    public function getMagentoOrdersNumberRegularPrefix(): string
    {
        $settings = $this->getSetting('magento_orders_settings', ['number', 'prefix']);

        return isset($settings['prefix']) ? $settings['prefix'] : '';
    }

    /**
     * @return string
     */
    public function getMagentoOrdersNumberAfnPrefix(): string
    {
        $settings = $this->getSetting('magento_orders_settings', ['number', 'prefix']);

        return isset($settings['afn-prefix']) ? $settings['afn-prefix'] : '';
    }

    /**
     * @return string
     */
    public function getMagentoOrdersNumberPrimePrefix(): string
    {
        $settings = $this->getSetting('magento_orders_settings', ['number', 'prefix']);

        return isset($settings['prime-prefix']) ? $settings['prime-prefix'] : '';
    }

    /**
     * @return string
     */
    public function getMagentoOrdersNumberB2bPrefix(): string
    {
        $settings = $this->getSetting('magento_orders_settings', ['number', 'prefix']);

        return isset($settings['b2b-prefix']) ? $settings['b2b-prefix'] : '';
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersNumberApplyToAmazonOrderEnable(): bool
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['number', 'apply_to_amazon'],
            0
        );

        return $setting == 1;
    }

    /**
     * @return int
     */
    public function getQtyReservationDays(): int
    {
        $setting = $this->getSetting('magento_orders_settings', ['qty_reservation', 'days'], 1);

        return (int)$setting;
    }

    /**
     * @return bool
     */
    public function isRefundEnabled(): bool
    {
        $setting = $this->getSetting('magento_orders_settings', ['refund_and_cancellation', 'refund_mode']);

        return (bool)$setting;
    }

    public function isCreateCreditMemoEnabled(): bool
    {
        $setting = $this->getSetting('magento_orders_settings', ['refund_and_cancellation', 'credit_memo']);

        return (bool)$setting;
    }

    public function isCreateCreditMemoBuyerRequestedCancelEnabled(): bool
    {
        $setting = $this->getSetting(
            'magento_orders_settings',
            ['refund_and_cancellation', 'credit_memo_buyer_requested_cancel']
        );

        return (bool)$setting;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersTaxModeNone(): bool
    {
        $setting = $this->getSetting('magento_orders_settings', ['tax', 'mode']);

        return $setting == self::MAGENTO_ORDERS_TAX_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersTaxModeChannel(): bool
    {
        $setting = $this->getSetting('magento_orders_settings', ['tax', 'mode']);

        return $setting == self::MAGENTO_ORDERS_TAX_MODE_CHANNEL;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersTaxModeMagento(): bool
    {
        $setting = $this->getSetting('magento_orders_settings', ['tax', 'mode']);

        return $setting == self::MAGENTO_ORDERS_TAX_MODE_MAGENTO;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersTaxModeMixed(): bool
    {
        $setting = $this->getSetting('magento_orders_settings', ['tax', 'mode']);

        return $setting == self::MAGENTO_ORDERS_TAX_MODE_MIXED;
    }

    /**
     * @return bool
     */
    public function isAmazonCollectsEnabled(): bool
    {
        return (bool)$this->getSetting('magento_orders_settings', ['tax', 'amazon_collects'], 0);
    }

    /**
     * @return array
     */
    public function getExcludedStates(): array
    {
        return $this->getSetting('magento_orders_settings', ['tax', 'excluded_states'], []);
    }

    /**
     * @return bool
     */
    public function isAmazonCollectsTaxForUKShipmentAvailable(): bool
    {
        $setting = $this->getSetting('magento_orders_settings', ['tax', 'amazon_collect_for_uk'], 0);

        return $setting == self::SKIP_TAX_FOR_UK_SHIPMENT;
    }

    /**
     * @return bool
     */
    public function isAmazonCollectsTaxForUKShipmentWithCertainPrice(): bool
    {
        $setting = $this->getSetting('magento_orders_settings', ['tax', 'amazon_collect_for_uk'], 0);

        return $setting == self::SKIP_TAX_FOR_UK_SHIPMENT_WITH_CERTAIN_PRICE;
    }

    /**
     * @return array
     */
    public function getExcludedCountries(): array
    {
        return $this->getSetting('magento_orders_settings', ['tax', 'excluded_countries'], []);
    }

    /**
     * @return bool
     */
    public function isAmazonCollectsTaxForEEAShipmentFromUkSite(): bool
    {
        $setting = $this->getSetting('magento_orders_settings', ['tax', 'amazon_collect_for_eea'], 0);

        return $setting == 1;
    }

    /**
     * @return bool
     */
    public function isEnabledRoundingOfTaxRateValue(): bool
    {
        if (
            $this->isMagentoOrdersTaxModeMixed()
            || $this->isMagentoOrdersTaxModeChannel()
        ) {
            return (bool)$this->getSetting(
                'magento_orders_settings',
                ['tax', 'round_of_rate_value'],
                self::MAGENTO_ORDERS_TAX_ROUND_OF_RATE_NO
            );
        }

        return false;
    }

    public function isEnabledImportTaxIdInMagentoOrder(): bool
    {
        return (bool)$this->getSetting(
            'magento_orders_settings',
            ['tax', 'import_tax_id_in_magento_order'],
            false
        );
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersCustomerGuest(): bool
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
    public function isMagentoOrdersCustomerPredefined(): bool
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
    public function isMagentoOrdersCustomerNew(): bool
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
    public function getMagentoOrdersCustomerId(): int
    {
        $setting = $this->getSetting('magento_orders_settings', ['customer', 'id']);

        return (int)$setting;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersCustomerNewSubscribed(): bool
    {
        return $this->getSetting('magento_orders_settings', ['customer', 'subscription_mode'], 0) == 1;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersCustomerNewNotifyWhenCreated(): bool
    {
        $setting = $this->getSetting('magento_orders_settings', ['customer', 'notifications', 'customer_created']);

        return (bool)$setting;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersCustomerNewNotifyWhenOrderCreated(): bool
    {
        $setting = $this->getSetting('magento_orders_settings', ['customer', 'notifications', 'order_created']);

        return (bool)$setting;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersCustomerNewNotifyWhenInvoiceCreated(): bool
    {
        $setting = $this->getSetting('magento_orders_settings', ['customer', 'notifications', 'invoice_created']);

        return (bool)$setting;
    }

    /**
     * @return int
     */
    public function getMagentoOrdersCustomerNewWebsiteId(): int
    {
        $setting = $this->getSetting('magento_orders_settings', ['customer', 'website_id']);

        return (int)$setting;
    }

    /**
     * @return int
     */
    public function getMagentoOrdersCustomerNewGroupId(): int
    {
        $setting = $this->getSetting('magento_orders_settings', ['customer', 'group_id']);

        return (int)$setting;
    }

    public function isImportBuyerCompanyName(): bool
    {
        return (bool)$this->getSetting(
            'magento_orders_settings',
            ['customer', 'import_buyer_company_name'],
            1
        );
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersStatusMappingDefault(): bool
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

    /**
     * @return bool
     */
    public function isMagentoOrdersFbaModeEnabled(): bool
    {
        return $this->getSetting('magento_orders_settings', ['fba', 'mode'], 1) == 1;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersFbaStoreModeEnabled(): bool
    {
        $setting = $this->getSetting('magento_orders_settings', ['fba', 'store_mode'], 0);

        return $setting == 1;
    }

    /**
     * @return int
     */
    public function getMagentoOrdersFbaStoreId(): int
    {
        $setting = $this->getSetting('magento_orders_settings', ['fba', 'store_id'], 0);

        return (int)$setting;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersFbaStockEnabled(): bool
    {
        return $this->getSetting('magento_orders_settings', ['fba', 'stock_mode'], 0) == 1;
    }

    /**
     * @return int
     */
    public function getAutoInvoicing(): int
    {
        return (int)$this->getData('auto_invoicing');
    }

    /**
     * @return bool
     */
    public function isAutoInvoicingDisabled(): bool
    {
        return $this->getAutoInvoicing() == self::AUTO_INVOICING_DISABLED;
    }

    /**
     * @return bool
     */
    public function isVatCalculationServiceEnabled(): bool
    {
        return $this->getAutoInvoicing() == self::AUTO_INVOICING_VAT_CALCULATION_SERVICE;
    }

    /**
     * @return bool
     */
    public function isUploadMagentoInvoices(): bool
    {
        return $this->getAutoInvoicing() == self::AUTO_INVOICING_UPLOAD_MAGENTO_INVOICES;
    }

    /**
     * @return int
     */
    public function getInvoiceGeneration(): int
    {
        return (int)$this->getData('invoice_generation');
    }

    /**
     * @return bool
     */
    public function isInvoiceGenerationByAmazon(): bool
    {
        return $this->getInvoiceGeneration() == self::INVOICE_GENERATION_BY_AMAZON;
    }

    /**
     * @return bool
     */
    public function isInvoiceGenerationByExtension(): bool
    {
        return $this->getInvoiceGeneration() == self::INVOICE_GENERATION_BY_EXTENSION;
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersInvoiceEnabled(): bool
    {
        return (bool)$this->getData('create_magento_invoice');
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersShipmentEnabled(): bool
    {
        return (bool)$this->getData('create_magento_shipment');
    }

    /**
     * @return bool
     */
    public function isMagentoOrdersShipmentEnabledForFBA(): bool
    {
        return (bool)$this->getData('create_magento_shipment_fba_orders');
    }

    public function isRemoteFulfillmentProgramEnabled(): bool
    {
        return (bool)$this->getData('remote_fulfillment_program_mode');
    }

    public function getFbaInventoryMode(): int
    {
        return (int)$this->getData(ResourceAccount::COLUMN_FBA_INVENTORY_MODE);
    }

    public function isEnabledFbaInventoryMode(): bool
    {
        return $this->getFbaInventoryMode() === 1;
    }

    public function getManageFbaInventorySourceName(): ?string
    {
        if (!$this->isEnabledFbaInventoryMode()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Manage FBA inventory was not enabled.');
        }

        return $this->getData(ResourceAccount::COLUMN_FBA_INVENTORY_SOURCE_NAME);
    }

    public function isCacheEnabled(): bool
    {
        return true;
    }

    public function isRegionOverrideRequired(): bool
    {
        return (bool)$this->getSetting(
            'magento_orders_settings',
            ['shipping_information', 'shipping_address_region_override'],
            1
        );
    }
}
