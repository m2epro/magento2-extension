<?php

namespace Ess\M2ePro\Model\ResourceModel\Walmart;

class Order extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    public const COLUMN_ORDER_ID = 'order_id';
    public const COLUMN_PURCHASE_CREATE_DATE = 'purchase_create_date';
    public const COLUMN_IS_TRIED_TO_ACKNOWLEDGE = 'is_tried_to_acknowledge';

    private \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory;
    /** @var bool  */
    protected $_isPkAutoIncrement = false;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);

        $this->walmartFactory = $walmartFactory;
    }

    public function _construct()
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_WALMART_ORDER,
            self::COLUMN_ORDER_ID
        );
        $this->_isPkAutoIncrement = false;
    }

    public function getItemsTotal($orderId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Order\Collection $collection */
        $collection = $this->walmartFactory->getObject('Order\Item')->getCollection();
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->addFieldToFilter('order_id', (int)$orderId);
        $collection->getSelect()->columns([
            'items_total' => new \Zend_Db_Expr('SUM((`price`)*`qty_purchased`)'),
        ]);
        $collection->getSelect()->group('order_id');

        return round((float)$collection->getFirstItem()->getData('items_total'), 2);
    }
}
