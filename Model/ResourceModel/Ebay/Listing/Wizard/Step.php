<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard;

use Ess\M2ePro\Helper\Module\Database\Tables;
use Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel;

class Step extends AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_WIZARD_ID = 'wizard_id';
    public const COLUMN_NICK = 'nick';
    public const COLUMN_DATA = 'data';
    public const COLUMN_IS_COMPLETED = 'is_completed';
    public const COLUMN_IS_SKIPPED = 'is_skipped';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    protected function _construct(): void
    {
        $this->_init(Tables::TABLE_NAME_EBAY_LISTING_WIZARD_STEP, self::COLUMN_ID);
    }
}
