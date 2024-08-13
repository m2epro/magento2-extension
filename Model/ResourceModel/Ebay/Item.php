<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay;

class Item extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_ITEM_ID = 'item_id';

    public function _construct(): void
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_ITEM,
            self::COLUMN_ID
        );
    }
}
