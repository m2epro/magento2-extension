<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product;

class AdvancedFilter extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_MODEL_NICK = 'model_nick';
    public const COLUMN_TITLE = 'title';
    public const COLUMN_CONDITIONALS = 'conditionals';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    protected function _construct()
    {
        $this->_init('m2epro_listing_product_advanced_filter', self::COLUMN_ID);
    }
}
