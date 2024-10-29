<?php

namespace Ess\M2ePro\Model\ResourceModel\Ebay;

class Account extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_ACCOUNT_ID = 'account_id';
    public const COLUMN_MODE = 'mode';
    public const COLUMN_SERVER_HASH = 'server_hash';
    public const COLUMN_USER_ID = 'user_id';
    public const COLUMN_IS_TOKEN_EXIST = 'is_token_exist';
    public const COLUMN_SELL_API_TOKEN_EXPIRED_DATE = 'sell_api_token_expired_date';
    public const COLUMN_MARKETPLACES_DATA = 'marketplaces_data';
    public const COLUMN_INVENTORY_LAST_SYNCHRONIZATION = 'inventory_last_synchronization';
    public const COLUMN_OTHER_LISTINGS_SYNCHRONIZATION = 'other_listings_synchronization';
    public const COLUMN_OTHER_LISTINGS_MAPPING_MODE = 'other_listings_mapping_mode';
    public const COLUMN_OTHER_LISTINGS_MAPPING_SETTINGS = 'other_listings_mapping_settings';
    public const COLUMN_OTHER_LISTINGS_LAST_SYNCHRONIZATION = 'other_listings_last_synchronization';
    public const COLUMN_FEEDBACKS_RECEIVE = 'feedbacks_receive';
    public const COLUMN_FEEDBACKS_AUTO_RESPONSE = 'feedbacks_auto_response';
    public const COLUMN_FEEDBACKS_AUTO_RESPONSE_ONLY_POSITIVE = 'feedbacks_auto_response_only_positive';
    public const COLUMN_FEEDBACKS_LAST_USED_ID = 'feedbacks_last_used_id';
    public const COLUMN_EBAY_SITE = 'ebay_site';
    public const COLUMN_EBAY_STORE_TITLE = 'ebay_store_title';
    public const COLUMN_EBAY_STORE_URL = 'ebay_store_url';
    public const COLUMN_EBAY_STORE_SUBSCRIPTION_LEVEL = 'ebay_store_subscription_level';
    public const COLUMN_EBAY_STORE_DESCRIPTION = 'ebay_store_description';
    public const COLUMN_INFO = 'info';
    public const COLUMN_USER_PREFERENCES = 'user_preferences';
    public const COLUMN_RATE_TABLES = 'rate_tables';
    public const COLUMN_EBAY_SHIPPING_DISCOUNT_PROFILES = 'ebay_shipping_discount_profiles';
    public const COLUMN_JOB_TOKEN = 'job_token';
    public const COLUMN_ORDERS_LAST_SYNCHRONIZATION = 'orders_last_synchronization';
    public const COLUMN_MAGENTO_ORDERS_SETTINGS = 'magento_orders_settings';
    public const COLUMN_CREATE_MAGENTO_INVOICE = 'create_magento_invoice';
    public const COLUMN_CREATE_MAGENTO_SHIPMENT = 'create_magento_shipment';
    public const COLUMN_SKIP_EVTIN = 'skip_evtin';
    public const COLUMN_MESSAGES_RECEIVE = 'messages_receive';

    /** @var bool */
    protected $_isPkAutoIncrement = false;

    public function _construct(): void
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_ACCOUNT,
            self::COLUMN_ACCOUNT_ID
        );
        $this->_isPkAutoIncrement = false;
    }
}
