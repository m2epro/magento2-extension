<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Amazon\Order
 */
class Order extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_order', 'order_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function hasGifts($orderId)
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Amazon\Order\Collection */
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Amazon::NICK, 'Order\Item')
            ->getCollection();
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->addFieldToFilter('order_id', $orderId);
        $collection->getSelect()->where('(gift_message != \'\' AND gift_message IS NOT NULL) OR gift_price != 0');

        return $collection->getSize();
    }

    //########################################

    public function getItemsTotal($orderId)
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\Amazon\Order\Collection */
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Amazon::NICK, 'Order\Item')
            ->getCollection();
        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->addFieldToFilter('order_id', (int)$orderId);
        $collection->getSelect()->columns([
            'items_total' => new \Zend_Db_Expr('SUM((`price` + `gift_price`)*`qty_purchased`)')
        ]);
        $collection->getSelect()->group('order_id');

        return round($collection->getFirstItem()->getData('items_total'), 2);
    }

    //########################################
}
