<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template;

class Synchronization extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_TEMPLATE_SYNCHRONIZATION_ID = 'template_synchronization_id';
    public const COLUMN_REVISE_UPDATE_PRODUCT_IDENTIFIERS = 'revise_update_product_identifiers';

    /** @var bool  */
    protected $_isPkAutoIncrement = false;

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_TEMPLATE_SYNCHRONIZATION,
            self::COLUMN_TEMPLATE_SYNCHRONIZATION_ID
        );
        $this->_isPkAutoIncrement = false;
    }
}
