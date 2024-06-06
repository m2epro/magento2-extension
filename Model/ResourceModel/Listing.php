<?php

namespace Ess\M2ePro\Model\ResourceModel;

class Listing extends ActiveRecord\Component\Parent\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_ACCOUNT_ID = 'account_id';

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_LISTING,
            self::COLUMN_ID
        );
    }
}
