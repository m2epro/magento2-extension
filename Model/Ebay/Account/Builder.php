<?php

namespace Ess\M2ePro\Model\Ebay\Account;

use Ess\M2ePro\Model\Ebay\Account as Account;
use Ess\M2ePro\Model\ResourceModel\Ebay\Account as EbayAccountResource;

class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    private \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);

        $this->activeRecordFactory = $activeRecordFactory;
    }

    protected function prepareData()
    {
        $data = [];

        // tab: general
        // ---------------------------------------
        $keys = [
            'title',
            'mode',
            'user_id',
            'is_token_exist',
            EbayAccountResource::COLUMN_INFO,
            EbayAccountResource::COLUMN_EBAY_SITE,
            'server_hash',
            'sell_api_token_expired_date',
        ];
        foreach ($keys as $key) {
            if (isset($this->rawData[$key])) {
                $data[$key] = $this->rawData[$key];
            }
        }

        // tab: Unmanaged
        // ---------------------------------------
        $keys = [
            'other_listings_synchronization',
            'other_listings_mapping_mode',
        ];
        foreach ($keys as $key) {
            if (isset($this->rawData[$key])) {
                $data[$key] = $this->rawData[$key];
            }
        }

        $marketplacesIds = $this->activeRecordFactory->getObject('Marketplace')->getCollection()
                                                     ->addFieldToFilter(
                                                         'component_mode',
                                                         \Ess\M2ePro\Helper\Component\Ebay::NICK
                                                     )
                                                     ->addFieldToFilter(
                                                         'status',
                                                         \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE
                                                     )
                                                     ->getColumnValues('id');

        $marketplacesData = [];
        if ($this->getModel()->getId()) {
            $marketplacesData = $this->getModel()->getChildObject()->getSettings(
                'marketplaces_data'
            );
        }

        foreach ($marketplacesIds as $marketplaceId) {
            $marketplacesData[$marketplaceId]['related_store_id'] =
                isset($this->rawData['related_store_id_' . $marketplaceId])
                    ? (int)$this->rawData['related_store_id_' . $marketplaceId]
                    : \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        }

        $data['marketplaces_data'] = \Ess\M2ePro\Helper\Json::encode($marketplacesData);

        // Mapping
        // ---------------------------------------
        $tempData = [];
        $keys = [
            'mapping_sku_mode',
            'mapping_sku_priority',
            'mapping_sku_attribute',

            'mapping_title_mode',
            'mapping_title_priority',
            'mapping_title_attribute',

            'mapping_item_id_mode',
            'mapping_item_id_priority',
            'mapping_item_id_attribute',
        ];
        foreach ($keys as $key) {
            if (isset($this->rawData[$key])) {
                $tempData[$key] = $this->rawData[$key];
            }
        }

        $mappingSettings = [];
        if ($this->getModel()->getId()) {
            $mappingSettings = $this->getModel()->getChildObject()->getSettings(
                'other_listings_mapping_settings'
            );
        }

        if (isset($tempData['mapping_sku_mode'])) {
            $mappingSettings['sku']['mode'] = (int)$tempData['mapping_sku_mode'];

            if (
                $tempData['mapping_sku_mode'] == Account::OTHER_LISTINGS_MAPPING_SKU_MODE_DEFAULT ||
                $tempData['mapping_sku_mode'] == Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE ||
                $tempData['mapping_sku_mode'] == Account::OTHER_LISTINGS_MAPPING_SKU_MODE_PRODUCT_ID
            ) {
                $mappingSettings['sku']['priority'] = (int)$tempData['mapping_sku_priority'];
            }

            if ($tempData['mapping_sku_mode'] == Account::OTHER_LISTINGS_MAPPING_SKU_MODE_CUSTOM_ATTRIBUTE) {
                $mappingSettings['sku']['attribute'] = (string)$tempData['mapping_sku_attribute'];
            }
        }

        if (isset($tempData['mapping_title_mode'])) {
            $mappingSettings['title']['mode'] = (int)$tempData['mapping_title_mode'];

            if (
                $tempData['mapping_title_mode'] == Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_DEFAULT ||
                $tempData['mapping_title_mode'] == Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE
            ) {
                $mappingSettings['title']['priority'] = (int)$tempData['mapping_title_priority'];
            }

            if ($tempData['mapping_title_mode'] == Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_CUSTOM_ATTRIBUTE) {
                $mappingSettings['title']['attribute'] = (string)$tempData['mapping_title_attribute'];
            }
        }

        if (isset($tempData['mapping_item_id_mode'])) {
            $mappingSettings['item_id']['mode'] = (int)$tempData['mapping_item_id_mode'];

            if ($tempData['mapping_item_id_mode'] == Account::OTHER_LISTINGS_MAPPING_ITEM_ID_MODE_CUSTOM_ATTRIBUTE) {
                $mappingSettings['item_id']['priority'] = (int)$tempData['mapping_item_id_priority'];
                $mappingSettings['item_id']['attribute'] = (string)$tempData['mapping_item_id_attribute'];
            }
        }

        $data['other_listings_mapping_settings'] = \Ess\M2ePro\Helper\Json::encode($mappingSettings);

        // tab: orders
        // ---------------------------------------
        $data['magento_orders_settings'] = [];
        if ($this->getModel()->getId()) {
            $data['magento_orders_settings'] = $this->getModel()->getChildObject()->getSettings(
                'magento_orders_settings'
            );
        }

        // m2e orders settings
        // ---------------------------------------
        $tempKey = 'listing';
        $tempSettings = !empty($this->rawData['magento_orders_settings'][$tempKey])
            ? $this->rawData['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
            'create_from_date',
            'store_mode',
            'store_id',
        ];
        foreach ($keys as $key) {
            if (!isset($tempSettings[$key])) {
                continue;
            }

            if ($key === 'create_from_date') {
                $tempSettings[$key] = $this->convertDate($tempSettings[$key]);
            }

            $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
        }

        // Unmanaged orders settings
        // ---------------------------------------
        $tempKey = 'listing_other';
        $tempSettings = !empty($this->rawData['magento_orders_settings'][$tempKey])
            ? $this->rawData['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
            'create_from_date',
            'product_mode',
            'product_tax_class_id',
            'store_id',
        ];
        foreach ($keys as $key) {
            if (!isset($tempSettings[$key])) {
                continue;
            }

            if ($key === 'create_from_date') {
                $tempSettings[$key] = $this->convertDate($tempSettings[$key]);
            }

            $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
        }

        // order number settings
        // ---------------------------------------
        $tempKey = 'number';
        $tempSettings = !empty($this->rawData['magento_orders_settings'][$tempKey])
            ? $this->rawData['magento_orders_settings'][$tempKey] : [];

        if (!empty($tempSettings['source'])) {
            $data['magento_orders_settings'][$tempKey]['source'] = $tempSettings['source'];
        }

        $prefixKeys = [
            'prefix',
            'use_marketplace_prefix',
        ];
        $tempSettings = !empty($tempSettings['prefix']) ? $tempSettings['prefix'] : [];
        foreach ($prefixKeys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey]['prefix'][$key] = $tempSettings[$key];
            }
        }

        // creation settings
        // ---------------------------------------
        $tempKey = 'creation';
        $tempSettings = !empty($this->rawData['magento_orders_settings'][$tempKey])
            ? $this->rawData['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        // tax settings
        // ---------------------------------------
        $tempKey = 'tax';
        $tempSettings = !empty($this->rawData['magento_orders_settings'][$tempKey])
            ? $this->rawData['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        // customer settings
        // ---------------------------------------
        $tempKey = 'customer';
        $tempSettings = !empty($this->rawData['magento_orders_settings'][$tempKey])
            ? $this->rawData['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
            'id',
            'website_id',
            'group_id',
            'billing_address_mode',
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        // Check if input data contains another field from customer settings.
        // It's used to determine if account data changed by user interface, or during token re-new.
        if (isset($tempSettings['mode'])) {
            $notificationsKeys = [
                'order_created',
                'invoice_created',
            ];
            $tempSettings = !empty($tempSettings['notifications']) ? $tempSettings['notifications'] : [];
            foreach ($notificationsKeys as $key) {
                $data['magento_orders_settings'][$tempKey]['notifications'][$key] = in_array($key, $tempSettings);
            }
        }

        // status mapping settings
        // ---------------------------------------
        $tempKey = 'status_mapping';
        $tempSettings = !empty($this->rawData['magento_orders_settings'][$tempKey])
            ? $this->rawData['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
            'new',
            'paid',
            'shipped',
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        // qty reservation
        // ---------------------------------------
        $tempKey = 'qty_reservation';
        $tempSettings = !empty($this->rawData['magento_orders_settings'][$tempKey])
            ? $this->rawData['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'days',
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        // refund & cancellation
        // ---------------------------------------
        $tempKey = 'refund_and_cancellation';
        $tempSettings = !empty($this->rawData['magento_orders_settings'][$tempKey])
            ? $this->rawData['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'refund_mode',
            'credit_memo',
            'approve_buyer_cancellation_requested',
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        // Shipping information
        // ---------------------------------------
        $tempKey = 'shipping_information';
        $tempSettings = !empty($this->rawData['magento_orders_settings'][$tempKey])
            ? $this->rawData['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'ship_by_date',
            'shipping_address_region_override',
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        // Final Fee
        // ---------------------------------------
        $tempKey = 'final_fee';
        $tempSettings = !empty($this->rawData['magento_orders_settings'][$tempKey])
            ? $this->rawData['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'auto_retrieve_enabled',
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        $data['magento_orders_settings'] = \Ess\M2ePro\Helper\Json::encode($data['magento_orders_settings']);

        // tab invoice and shipment
        // ---------------------------------------
        $keys = [
            'create_magento_invoice',
            'create_magento_shipment',
            'skip_evtin',
        ];
        foreach ($keys as $key) {
            if (isset($this->rawData[$key])) {
                $data[$key] = $this->rawData[$key];
            }
        }

        // tab: feedbacks
        // ---------------------------------------
        $keys = [
            'feedbacks_receive',
            'feedbacks_auto_response',
            'feedbacks_auto_response_only_positive',
        ];
        foreach ($keys as $key) {
            if (isset($this->rawData[$key])) {
                $data[$key] = $this->rawData[$key];
            }
        }

        return $data;
    }

    /**
     * @param \DateTime|string $date
     */
    private function convertDate($date): string
    {
        if (is_string($date)) {
            return $date;
        }

        return \Ess\M2ePro\Helper\Date::createWithGmtTimeZone($date)->format('Y-m-d H:i:s');
    }

    public function getDefaultData(): array
    {
        return [
            'title' => '',
            'user_id' => '',
            'mode' => Account::MODE_PRODUCTION,
            'server_hash' => '',
            'sell_api_token_expired_date' => '',

            'other_listings_synchronization' => 1,
            'other_listings_mapping_mode' => 1,
            'other_listings_mapping_settings' => [
                'sku' => [
                    'mode' => Account::OTHER_LISTINGS_MAPPING_SKU_MODE_DEFAULT,
                    'priority' => 1,
                ],
            ],
            'mapping_sku_mode' => Account::OTHER_LISTINGS_MAPPING_SKU_MODE_DEFAULT,
            'mapping_sku_priority' => 1,

            'magento_orders_settings' => [
                'listing' => [
                    'mode' => 1,
                    'create_from_date' => null,
                    'store_mode' => Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT,
                    'store_id' => null,
                ],
                'listing_other' => [
                    'mode' => 1,
                    'create_from_date' => null,
                    'product_mode' => Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE,
                    'product_tax_class_id' => \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE,
                    'store_id' => null,
                ],
                'number' => [
                    'source' => Account::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO,
                    'prefix' => [
                        'prefix' => '',
                        'use_marketplace_prefix' => 0,
                    ],
                ],
                'customer' => [
                    'mode' => Account::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST,
                    'id' => null,
                    'website_id' => null,
                    'group_id' => null,
                    'notifications' => [
                        'invoice_created' => false,
                        'order_created' => false,
                    ],
                ],
                'creation' => [
                    'mode' => Account::MAGENTO_ORDERS_CREATE_CHECKOUT_AND_PAID,
                ],
                'tax' => [
                    'mode' => Account::MAGENTO_ORDERS_TAX_MODE_MIXED,
                ],
                'status_mapping' => [
                    'mode' => Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT,
                    'new' => Account::MAGENTO_ORDERS_STATUS_MAPPING_NEW,
                    'paid' => Account::MAGENTO_ORDERS_STATUS_MAPPING_PAID,
                    'shipped' => Account::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED,
                ],
                'qty_reservation' => [
                    'days' => 1,
                ],
                'refund_and_cancellation' => [
                    'refund_mode' => 0,
                    'credit_memo' => 0,
                    'approve_buyer_cancellation_requested' => 0,
                ],
                'shipping_information' => [
                    'ship_by_date' => 1,
                    'shipping_address_region_override' => 1,
                ],
                'final_fee' => [
                    'auto_retrieve_enabled' => 0,
                ],
            ],

            'create_magento_invoice' => 1,
            'create_magento_shipment' => 1,
            'skip_evtin' => 0,

            EbayAccountResource::COLUMN_EBAY_SITE => '',
            'ebay_store_title' => '',
            'ebay_store_url' => '',
            'ebay_store_subscription_level' => '',
            'ebay_store_description' => '',

            EbayAccountResource::COLUMN_INFO => json_encode([]),

            'feedbacks_receive' => 0,
            'feedbacks_auto_response' => Account::FEEDBACKS_AUTO_RESPONSE_NONE,
            'feedbacks_auto_response_only_positive' => 0,
        ];
    }
}
