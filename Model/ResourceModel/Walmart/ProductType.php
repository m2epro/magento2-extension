<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Walmart;

class ProductType extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_VIEW_MODE = 'view_mode';
    public const COLUMN_DICTIONARY_PRODUCT_TYPE_ID = 'dictionary_product_type_id';
    public const COLUMN_ATTRIBUTES_SETTINGS = 'attributes_settings';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_PRODUCT_TYPE,
            self::COLUMN_ID
        );
    }
}
