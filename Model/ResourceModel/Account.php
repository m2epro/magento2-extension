<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel;

class Account extends ActiveRecord\Component\Parent\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_CREATE_DATE = 'create_date';

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_ACCOUNT,
            self::COLUMN_ID
        );
    }
}
