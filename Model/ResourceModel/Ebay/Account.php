<?php

namespace Ess\M2ePro\Model\ResourceModel\Ebay;

class Account extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_ACCOUNT_ID = 'account_id';
    public const COLUMN_SKIP_EVTIN = 'skip_evtin';

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
