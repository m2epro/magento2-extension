<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing;

use Ess\M2ePro\Helper\Module\Database\Tables;
use Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel;

class Wizard extends AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_LISTING_ID = 'listing_id';
    public const COLUMN_TYPE = 'type';
    public const COLUMN_CURRENT_STEP_NICK = 'current_step_nick';
    public const COLUMN_PROCESS_START_DATE = 'process_start_date';
    public const COLUMN_PROCESS_END_DATE = 'process_end_date';
    public const COLUMN_PRODUCT_COUNT_TOTAL = 'product_count_total';
    public const COLUMN_IS_COMPLETED = 'is_completed';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    protected function _construct(): void
    {
        $this->_init(Tables::TABLE_NAME_EBAY_LISTING_WIZARD, self::COLUMN_ID);
    }
}
