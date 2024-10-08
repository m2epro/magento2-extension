<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary;

class Marketplace extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_MARKETPLACE_ID = 'marketplace_id';
    public const COLUMN_CLIENT_DETAILS_LAST_UPDATE_DATE = 'client_details_last_update_date';
    public const COLUMN_SERVER_DETAILS_LAST_UPDATE_DATE = 'server_details_last_update_date';
    public const COLUMN_PRODUCT_TYPES = 'product_types';

    protected function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_DICTIONARY_MARKETPLACE,
            self::COLUMN_ID
        );
    }
}
