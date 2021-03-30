<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Account;

use Ess\M2ePro\Model\Ebay\Account as Account;

/**
 * Class Ess\M2ePro\Model\Ebay\Account\Builder
 */
class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    protected $activeRecordFactory;
    protected $ebayFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    protected function prepareData()
    {
        $data = [];

        // tab: general
        // ---------------------------------------
        $keys = [
            'title',
            'mode',
            'user_id',
            'info',
            'server_hash',
            'token_session',
            'token_expired_date',
            'sell_api_token_session',
            'sell_api_token_expired_date'
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
            'other_listings_mapping_mode'
        ];
        foreach ($keys as $key) {
            if (isset($this->rawData[$key])) {
                $data[$key] = $this->rawData[$key];
            }
        }

        $marketplacesIds = $this->activeRecordFactory->getObject('Marketplace')->getCollection()
            ->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Ebay::NICK)
            ->addFieldToFilter('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)
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

        $data['marketplaces_data'] = $this->getHelper('Data')->jsonEncode($marketplacesData);

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
            'mapping_item_id_attribute'
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

            if ($tempData['mapping_sku_mode'] == Account::OTHER_LISTINGS_MAPPING_SKU_MODE_DEFAULT ||
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

            if ($tempData['mapping_title_mode'] == Account::OTHER_LISTINGS_MAPPING_TITLE_MODE_DEFAULT ||
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

        $data['other_listings_mapping_settings'] = $this->getHelper('Data')->jsonEncode($mappingSettings);

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
            'store_mode',
            'store_id'
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        // Unmanaged orders settings
        // ---------------------------------------
        $tempKey = 'listing_other';
        $tempSettings = !empty($this->rawData['magento_orders_settings'][$tempKey])
            ? $this->rawData['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
            'product_mode',
            'product_tax_class_id',
            'store_id'
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
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
            'use_marketplace_prefix'
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
            'mode'
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
            'mode'
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

        $notificationsKeys = [
            'order_created',
            'invoice_created'
        ];
        $tempSettings = !empty($tempSettings['notifications']) ? $tempSettings['notifications'] : [];
        foreach ($notificationsKeys as $key) {
            $data['magento_orders_settings'][$tempKey]['notifications'][$key] = in_array($key, $tempSettings);
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
            'shipped'
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
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        // In Store Pickup statuses
        // ---------------------------------------
        $tempKey = 'in_store_pickup_statuses';
        $tempSettings = !empty($this->rawData['magento_orders_settings'][$tempKey])
            ? $this->rawData['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
            'ready_for_pickup',
            'picked_up',
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        $data['magento_orders_settings'] = $this->getHelper('Data')->jsonEncode($data['magento_orders_settings']);

        // tab invoice and shipment
        // ---------------------------------------
        $keys = [
            'create_magento_invoice',
            'create_magento_shipment',
            'skip_evtin'
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
            'feedbacks_auto_response_only_positive'
        ];
        foreach ($keys as $key) {
            if (isset($this->rawData[$key])) {
                $data[$key] = $this->rawData[$key];
            }
        }

        // tab: My Stores
        // ---------------------------------------
        if (isset($this->rawData['pickup_store_mode'])) {
            $data['additional_data'] = $this->getHelper('Data')->jsonEncode(
                ['bopis' => $this->rawData['pickup_store_mode']]
            );
        }

        return $data;
    }

    public function getDefaultData()
    {
        return [
            'title'                       => '',
            'user_id'                     => '',
            'mode'                        => Account::MODE_PRODUCTION,
            'server_hash'                 => '',
            'token_session'               => '',
            'token_expired_date'          => '',
            'sell_api_token_session'      => '',
            'sell_api_token_expired_date' => '',

            'other_listings_synchronization'  => 1,
            'other_listings_mapping_mode'     => 0,
            'other_listings_mapping_settings' => [],

            'magento_orders_settings' => [
                'listing'                  => [
                    'mode'       => 1,
                    'store_mode' => Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT,
                    'store_id'   => null
                ],
                'listing_other'            => [
                    'mode'                 => 1,
                    'product_mode'         => Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE,
                    'product_tax_class_id' => \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE,
                    'store_id'             => null,
                ],
                'number'                   => [
                    'source' => Account::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO,
                    'prefix' => [
                        'prefix'                 => '',
                        'use_marketplace_prefix' => 0,
                    ],
                ],
                'customer'                 => [
                    'mode'                 => Account::MAGENTO_ORDERS_CUSTOMER_MODE_GUEST,
                    'id'                   => null,
                    'website_id'           => null,
                    'group_id'             => null,
                    'notifications'        => [
                        'invoice_created' => false,
                        'order_created'   => false
                    ],
                    'billing_address_mode' =>
                        Account::USE_SHIPPING_ADDRESS_AS_BILLING_IF_SAME_CUSTOMER_AND_RECIPIENT
                ],
                'creation'                 => [
                    'mode' => Account::MAGENTO_ORDERS_CREATE_CHECKOUT_AND_PAID,
                ],
                'tax'                      => [
                    'mode' => Account::MAGENTO_ORDERS_TAX_MODE_MIXED
                ],
                'in_store_pickup_statuses' => [
                    'mode'             => 0,
                    'ready_for_pickup' => '',
                    'picked_up'        => '',
                ],
                'status_mapping'           => [
                    'mode'    => Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT,
                    'new'     => Account::MAGENTO_ORDERS_STATUS_MAPPING_NEW,
                    'paid'    => Account::MAGENTO_ORDERS_STATUS_MAPPING_PAID,
                    'shipped' => Account::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED
                ],
                'qty_reservation'          => [
                    'days' => 1
                ],
                'refund_and_cancellation'  => [
                    'refund_mode' => 0,
                ],
            ],

            'create_magento_invoice'  => 1,
            'create_magento_shipment' => 1,
            'skip_evtin'              => 0,

            'ebay_store_title'              => '',
            'ebay_store_url'                => '',
            'ebay_store_subscription_level' => '',
            'ebay_store_description'        => '',

            'feedbacks_receive'                     => 0,
            'feedbacks_auto_response'               => Account::FEEDBACKS_AUTO_RESPONSE_NONE,
            'feedbacks_auto_response_only_positive' => 0
        ];
    }
}
