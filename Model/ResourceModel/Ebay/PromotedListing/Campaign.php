<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\PromotedListing;

class Campaign extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_ACCOUNT_ID = 'account_id';
    public const COLUMN_MARKETPLACE_ID = 'marketplace_id';
    public const COLUMN_EBAY_CAMPAIGN_ID = 'ebay_campaign_id';
    public const COLUMN_NAME = 'name';
    public const COLUMN_TYPE = 'type';
    public const COLUMN_STATUS = 'status';
    public const COLUMN_START_DATE = 'start_date';
    public const COLUMN_END_DATE = 'end_date';
    public const COLUMN_RATE = 'rate';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    protected function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_PROMOTED_LISTING_CAMPAIGN,
            self::COLUMN_ID
        );
    }
}
