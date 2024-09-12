<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary;

class ProductType extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_MARKETPLACE_ID = 'marketplace_id';
    public const COLUMN_NICK = 'nick';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_SCHEMA = 'scheme';
    public const COLUMN_VARIATION_THEMES = 'variation_themes';
    public const COLUMN_ATTRIBUTES_GROUPS = 'attributes_groups';
    public const COLUMN_CLIENT_DETAILS_LAST_UPDATE_DATE = 'client_details_last_update_date';
    public const COLUMN_SERVER_DETAILS_LAST_UPDATE_DATE = 'server_details_last_update_date';
    public const COLUMN_INVALID = 'invalid';

    protected function _construct(): void
    {
        $this->_init(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_DICTIONARY_PRODUCT_TYPE, self::COLUMN_ID);
    }
}
