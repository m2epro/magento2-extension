<?php

namespace Ess\M2ePro\Model\ResourceModel;

class Order extends ActiveRecord\Component\Parent\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_ACCOUNT_ID = 'account_id';
    public const COLUMN_MAGENTO_ORDER_ID = 'magento_order_id';

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_ORDER,
            self::COLUMN_ID
        );
    }
}
