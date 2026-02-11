<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Template;

class Repricer extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_ACCOUNT_ID = 'account_id';

    public const COLUMN_MIN_PRICE_MODE = 'min_price_mode';
    public const COLUMN_MIN_PRICE_ATTRIBUTE = 'min_price_attribute';
    public const COLUMN_MAX_PRICE_MODE = 'max_price_mode';
    public const COLUMN_MAX_PRICE_ATTRIBUTE = 'max_price_attribute';
    public const COLUMN_STRATEGY_NAME = 'strategy_name';

    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_TEMPLATE_REPRICER,
            self::COLUMN_ID
        );
    }
}
