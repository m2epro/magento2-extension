<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Dictionary;

class Marketplace extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_MARKETPLACE_ID = 'marketplace_id';
    public const COLUMN_PRODUCT_TYPES = 'product_types';

    protected function _construct(): void
    {
        $this->_init(\Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_DICTIONARY_MARKETPLACE, self::COLUMN_ID);
    }
}
