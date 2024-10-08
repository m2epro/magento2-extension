<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Walmart\Dictionary;

class ProductType extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_MARKETPLACE_ID = 'marketplace_id';
    public const COLUMN_NICK = 'nick';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_ATTRIBUTES = 'attributes';
    public const COLUMN_VARIATION_ATTRIBUTES = 'variation_attributes';
    public const COLUMN_INVALID = 'invalid';

    protected function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_DICTIONARY_PRODUCT_TYPE,
            self::COLUMN_ID
        );
    }
}
