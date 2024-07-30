<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Bundle\Options;

class Mapping extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_OPTION_TITLE = 'option_title';
    public const COLUMN_ATTRIBUTE_CODE = 'attribute_code';

    protected function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_BUNDLE_OPTIONS_MAPPING,
            self::COLUMN_ID
        );
    }
}
