<?php

namespace Ess\M2ePro\Model\ResourceModel\Amazon;

class Order extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_ORDER_ID = 'order_id';
    public const COLUMN_AMAZON_ORDER_ID = 'amazon_order_id';
    public const COLUMN_IS_INVOICE_SENT = 'is_invoice_sent';
    public const COLUMN_DATE_OF_INVOICE_SENDING = 'date_of_invoice_sending';
    public const COLUMN_PURCHASE_CREATE_DATE = 'purchase_create_date';

    /** @var bool  */
    protected $_isPkAutoIncrement = false;

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_AMAZON_ORDER,
            self::COLUMN_ORDER_ID
        );
        $this->_isPkAutoIncrement = false;
    }

    public function hasGifts($orderId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Order\Collection $collection */
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Amazon::NICK, 'Order\Item')
                                          ->getCollection();
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->addFieldToFilter('order_id', $orderId);
        $collection->getSelect()->where('(gift_message != \'\' AND gift_message IS NOT NULL) OR gift_price != 0');

        return $collection->getSize();
    }

    public function getItemsTotal($orderId): float
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Order\Collection $collection */
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Amazon::NICK, 'Order\Item')
                                          ->getCollection();
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->addFieldToFilter('order_id', (int)$orderId);
        $collection->getSelect()->columns([
            'items_total' => new \Zend_Db_Expr('SUM((`price` + `gift_price`)*`qty_purchased`)'),
        ]);
        $collection->getSelect()->group('order_id');

        return round((float)$collection->getFirstItem()->getData('items_total'), 2);
    }
}
