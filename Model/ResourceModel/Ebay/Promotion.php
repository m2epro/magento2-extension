<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay;

class Promotion extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_ACCOUNT_ID = 'account_id';
    public const COLUMN_MARKETPLACE_ID = 'marketplace_id';
    public const COLUMN_PROMOTION_ID = 'promotion_id';
    public const COLUMN_NAME = 'name';
    public const COLUMN_TYPE = 'type';
    public const COLUMN_STATUS = 'status';
    public const COLUMN_PRIORITY = 'priority';
    public const COLUMN_DESCRIPTION = 'description';
    public const COLUMN_IMAGE = 'image';
    public const COLUMN_START_DATE = 'start_date';
    public const COLUMN_END_DATE = 'end_date';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    protected function _construct(): void
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_PROMOTION,
            self::COLUMN_ID
        );
    }
}
