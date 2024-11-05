<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\AttributeMapping;

class Pair extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_COMPONENT = 'component';
    public const COLUMN_TYPE = 'type';
    public const COLUMN_CHANNEL_ATTRIBUTE_TITLE = 'channel_attribute_title';
    public const COLUMN_CHANNEL_ATTRIBUTE_CODE = 'channel_attribute_code';
    public const COLUMN_MAGENTO_ATTRIBUTE_CODE = 'magento_attribute_code';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    protected function _construct(): void
    {
        $this->_init(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_ATTRIBUTE_MAPPING, self::COLUMN_ID);
    }
}
