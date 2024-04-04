<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Account;

class MerchantSetting extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_MERCHANT_ID = 'merchant_id';
    public const COLUMN_FBA_INVENTORY_MODE = 'fba_inventory_mode';
    public const COLUMN_FBA_INVENTORY_SOURCE_NAME = 'fba_inventory_source_name';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    protected function _construct(): void
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_ACCOUNT_MERCHANT_SETTING,
            self::COLUMN_MERCHANT_ID
        );
        $this->_isPkAutoIncrement = false;
    }
}
