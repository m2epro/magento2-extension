<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay;

class Order extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_order', 'order_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function getOrdersContainingItemsFromOrder($accountId, array $items)
    {
        // Prepare item_id-transaction_id pairs for sql
        // ---------------------------------------
        $connection = $this->getConnection();

        $whereSql = array();
        foreach ($items as $orderItem) {
            $itemIdSql = $connection->quoteInto('?', $orderItem['item_id']);
            $transactionIdSql = $connection->quoteInto('?', $orderItem['transaction_id']);

            $whereSql[] = "(item_id = {$itemIdSql} AND transaction_id = {$transactionIdSql})";
        }
        $whereSql = implode(' OR ', $whereSql);
        // ---------------------------------------

        $oiTable = $this->activeRecordFactory->getObject('Order\Item')->getResource()->getMainTable();
        $eoiTable = $this->activeRecordFactory->getObject('Ebay\Order\Item')->getResource()->getMainTable();

        // Find orders which contains at least one order item from current order
        // ---------------------------------------
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel */
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Ebay::NICK, 'Order')
            ->getCollection();
        $collection
            ->getSelect()
                ->distinct(true)
                ->join(
                    array('moi' => $oiTable),
                    '(`moi`.`order_id` = `main_table`.`id`)',
                    array()
                )
                ->join(
                    array('meoi' => $eoiTable),
                    '(`meoi`.`order_item_id` = `moi`.`id`)',
                    array()
                )
                ->where($whereSql)
                ->where('`main_table`.`account_id` = ?', $accountId)
                ->order(array('main_table.id ASC'));
        // ---------------------------------------

        return $collection->getItems();
    }

    //########################################

    public function getCancellationCandidatesChannelIds($accountId, \DateTime $startDate, \DateTime $endDate)
    {
        /** @var $collection \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel */
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Ebay::NICK, 'Order')
            ->getCollection();
        $collection->addFieldToFilter('account_id', $accountId);

        $collection->addFieldToFilter('payment_status', array(
            'neq' => \Ess\M2ePro\Model\Ebay\Order::PAYMENT_STATUS_COMPLETED
        ));

        $collection->addFieldToFilter('purchase_create_date', array(
            'from' => $startDate->format('Y-m-d H:i:s'),
            'to'   => $endDate->format('Y-m-d H:i:s')
        ));

        $collection->setOrder('id', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        $ebayOrdersIds = array();
        foreach ($collection->getItems() as $order) {
            $ebayOrdersIds[] = $order->getChildObject()->getEbayOrderId();
        }

        return $ebayOrdersIds;
    }

    //########################################
}