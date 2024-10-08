<?php

namespace Ess\M2ePro\Model\ResourceModel;

class Wizard extends ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WIZARD,
            self::COLUMN_ID
        );
    }
}
