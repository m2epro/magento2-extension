<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Listing\Auto;

class Category extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const CATEGORY_ID_FIELD = 'category_id';
    public const GROUP_ID_FIELD = 'group_id';

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_LISTING_AUTO_CATEGORY,
            self::COLUMN_ID
        );
    }
}
