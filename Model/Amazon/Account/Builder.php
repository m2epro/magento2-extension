<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Account;

use Ess\M2ePro\Model\Amazon\Account;

/**
 * Class Ess\M2ePro\Model\Amazon\Account\Builder
 */
class Builder extends \Ess\M2ePro\Model\ActiveRecord\AbstractBuilder
{
    //########################################

    protected function prepareData()
    {
        $data = [];

        // tab: general
        // ---------------------------------------
        $keys = [
            'title',
            'marketplace_id',
            'merchant_id',
            'token',
        ];
        foreach ($keys as $key) {
            if (isset($this->rawData[$key])) {
                $data[$key] = $this->rawData[$key];
            }
        }

        // tab: Unmanaged listings
        // ---------------------------------------
        $keys = [
            'related_store_id',

            'other_listings_synchronization',
            'other_listings_mapping_mode'
        ];
        foreach ($keys as $key) {
            if (isset($this->rawData[$key])) {
                $data[$key] = $this->rawData[$key];
            }
        }

        // Mapping
        // ---------------------------------------
        $tempData = [];
        $keys = [
            'mapping_general_id_mode',
            'mapping_general_id_priority',
            'mapping_general_id_attribute',

            'mapping_sku_mode',
            'mapping_sku_priority',
            'mapping_sku_attribute',

            'mapping_title_mode',
            'mapping_title_priority',
            'mapping_title_attribute'
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

        if (isset($tempData['mapping_general_id_mode'])) {
            $mappingSettings['general_id']['mode'] = (int)$tempData['mapping_general_id_mode'];

            if ($tempData['mapping_general_id_mode'] ==
                Account::OTHER_LISTINGS_MAPPING_GENERAL_ID_MODE_CUSTOM_ATTRIBUTE
            ) {
                $mappingSettings['general_id']['priority'] = (int)$tempData['mapping_general_id_priority'];
                $mappingSettings['general_id']['attribute'] = (string)$tempData['mapping_general_id_attribute'];
            }
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

        if (!empty($tempSettings['apply_to_amazon'])) {
            $data['magento_orders_settings'][$tempKey]['apply_to_amazon'] = $tempSettings['apply_to_amazon'];
        }

        $prefixKeys = [
            'prefix',
            'afn-prefix',
            'prime-prefix',
            'b2b-prefix',
        ];
        $tempSettings = !empty($tempSettings['prefix']) ? $tempSettings['prefix'] : [];
        foreach ($prefixKeys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey]['prefix'][$key] = $tempSettings[$key];
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

        // fba
        // ---------------------------------------
        $tempKey = 'fba';
        $tempSettings = !empty($this->rawData['magento_orders_settings'][$tempKey])
            ? $this->rawData['magento_orders_settings'][$tempKey] : [];

        $keys = [
            'mode',
            'stock_mode'
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
            'amazon_collect_for_uk'
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        if (isset($tempSettings['amazon_collects'])) {
            if ($this->isNeedExcludeStates()) {
                $data['magento_orders_settings'][$tempKey]['amazon_collects'] = $tempSettings['amazon_collects'];
            } else {
                $data['magento_orders_settings'][$tempKey]['amazon_collects'] = 0;
            }
        }

        if (isset($tempSettings['excluded_states'])) {
            $data['magento_orders_settings'][$tempKey]['excluded_states'] = explode(
                ',',
                $tempSettings['excluded_states']
            );
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
            'processing',
            'shipped'
        ];
        foreach ($keys as $key) {
            if (isset($tempSettings[$key])) {
                $data['magento_orders_settings'][$tempKey][$key] = $tempSettings[$key];
            }
        }

        $data['magento_orders_settings'] = $this->getHelper('Data')->jsonEncode($data['magento_orders_settings']);

        // tab: vat calculation service
        // ---------------------------------------
        $keys = [
            'auto_invoicing',
            'invoice_generation',
            'create_magento_invoice',
            'create_magento_shipment',
        ];
        foreach ($keys as $key) {
            if (isset($this->rawData[$key])) {
                $data[$key] = $this->rawData[$key];
            }
        }

        return $data;
    }

    public function getDefaultData()
    {
        return [
            // general
            'title'            => '',
            'marketplace_id'   => 0,
            'merchant_id'      => '',
            'token'            => '',

            // listing_other
            'related_store_id' => 0,

            'other_listings_synchronization'  => 1,
            'other_listings_mapping_mode'     => 1,
            'other_listings_mapping_settings' => [],

            // order
            'magento_orders_settings'         => [
                'listing'                 => [
                    'mode'       => 1,
                    'store_mode' => Account::MAGENTO_ORDERS_LISTINGS_STORE_MODE_DEFAULT,
                    'store_id'   => null
                ],
                'listing_other'           => [
                    'mode'                 => 1,
                    'product_mode'         => Account::MAGENTO_ORDERS_LISTINGS_OTHER_PRODUCT_MODE_IGNORE,
                    'product_tax_class_id' => \Ess\M2ePro\Model\Magento\Product::TAX_CLASS_ID_NONE,
                    'store_id'             => null,
                ],
                'number'                  => [
                    'source'          => Account::MAGENTO_ORDERS_NUMBER_SOURCE_MAGENTO,
                    'prefix'          => [
                        'mode'         => 0,
                        'prefix'       => '',
                        'afn-prefix'   => '',
                        'prime-prefix' => '',
                        'b2b-prefix'   => '',
                    ],
                    'apply_to_amazon' => 0
                ],
                'tax'                     => [
                    'mode'                  => Account::MAGENTO_ORDERS_TAX_MODE_MIXED,
                    'amazon_collects'       => 1,
                    'excluded_states'       => $this->getGeneralExcludedStates(),
                    'amazon_collect_for_uk' => Account::SKIP_TAX_FOR_UK_SHIPMENT_NONE
                ],
                'customer'                => [
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
                'status_mapping'          => [
                    'mode'       => Account::MAGENTO_ORDERS_STATUS_MAPPING_MODE_DEFAULT,
                    'processing' => Account::MAGENTO_ORDERS_STATUS_MAPPING_PROCESSING,
                    'shipped'    => Account::MAGENTO_ORDERS_STATUS_MAPPING_SHIPPED,
                ],
                'qty_reservation'         => [
                    'days' => 1
                ],
                'refund_and_cancellation' => [
                    'refund_mode' => 1,
                ],
                'fba'                     => [
                    'mode'       => 1,
                    'stock_mode' => 0
                ]
            ],

            // vcs_upload_invoices
            'auto_invoicing'                  => 0,
            'invoice_generation'              => 0,
            'create_magento_invoice'          => 1,
            'create_magento_shipment'         => 1
        ];
    }

    private function isNeedExcludeStates()
    {
        if ($this->rawData['marketplace_id'] != \Ess\M2ePro\Helper\Component\Amazon::MARKETPLACE_US) {
            return false;
        }

        if ($this->rawData['magento_orders_settings']['listing']['mode'] == 0 &&
            $this->rawData['magento_orders_settings']['listing_other']['mode'] == 0) {
            return false;
        }

        if (!isset($this->rawData['magento_orders_settings']['tax']['excluded_states'])) {
            return false;
        }

        return true;
    }

    private function getGeneralExcludedStates()
    {
        return [
            'AL',
            'AK',
            'AZ',
            'AR',
            'CA',
            'CO',
            'CT',
            'DC',
            'GA',
            'HI',
            'ID',
            'IL',
            'IN',
            'IA',
            'KY',
            'LA',
            'ME',
            'MD',
            'MA',
            'MI',
            'MN',
            'MS',
            'NE',
            'NV',
            'NJ',
            'NM',
            'NY',
            'NC',
            'ND',
            'OH',
            'OK',
            'PA',
            'PR',
            'RI',
            'SC',
            'SD',
            'TX',
            'UT',
            'VT',
            'VA',
            'WA',
            'WV',
            'WI',
            'WY',
        ];
    }

    //########################################
}
