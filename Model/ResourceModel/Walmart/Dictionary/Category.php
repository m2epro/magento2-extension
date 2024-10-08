<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary;

class Category extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_MARKETPLACE_ID = 'marketplace_id';
    public const COLUMN_CATEGORY_ID = 'category_id';
    public const COLUMN_PARENT_CATEGORY_ID = 'parent_category_id';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_IS_LEAF = 'is_leaf';
    public const COLUMN_PRODUCT_TYPE_NICK = 'product_type_nick';
    public const COLUMN_PRODUCT_TYPE_TITLE = 'product_type_title';

    protected function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_DICTIONARY_CATEGORY,
            self::COLUMN_ID
        );
    }
}
