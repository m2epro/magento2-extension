<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Order;

class Item extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ORDER_ITEM_ID = 'order_item_id';
    public const COLUMN_IS_SHIPPING_PALLET_DELIVERY = 'is_shipping_pallet_delivery';
    public const COLUMN_CUSTOMIZATION_DETAILS = 'customization_details';

    /** @var bool  */
    protected $_isPkAutoIncrement = false;

    public function _construct(): void
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_ORDER_ITEM,
            self::COLUMN_ORDER_ITEM_ID
        );
        $this->_isPkAutoIncrement = false;
    }
}
