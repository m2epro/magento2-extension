<?php

namespace Ess\M2ePro\Model\ResourceModel\Ebay;

class Marketplace extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_MARKETPLACE_ID = 'marketplace_id';
    public const COLUMN_IS_INTERNATIONAL_SHIPPING_RATE_TABLE = 'is_international_shipping_rate_table';

    /** @var bool  */
    protected $_isPkAutoIncrement = false;

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_MARKETPLACE,
            self::COLUMN_MARKETPLACE_ID
        );
        $this->_isPkAutoIncrement = false;
    }
}
