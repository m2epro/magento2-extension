<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Order;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Order\Change
 */
class Change extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_order_change', 'id');
    }

    //########################################

    public function deleteByIds(array $ids)
    {
        $this->getConnection()->delete(
            $this->getMainTable(),
            [
                'id IN(?)' => $ids
            ]
        );
    }

    public function deleteByOrderAction($orderId, $action)
    {
        $this->getConnection()->delete(
            $this->getMainTable(),
            [
                'order_id = ?' => $orderId,
                'action = ?' => $action
            ]
        );
    }

    public function deleteByProcessingAttemptCount($count = 3, $component = null)
    {
        $count = (int)$count;

        if ($count <= 0) {
            return;
        }

        $where = [
            'processing_attempt_count >= ?' => $count
        ];

        if ($component !== null) {
            $where['component = ?'] = $component;
        }

        $this->getConnection()->delete(
            $this->getMainTable(),
            $where
        );
    }

    //########################################

    public function incrementAttemptCount(array $ids, $increment = 1)
    {
        $increment = (int)$increment;

        if ($increment <= 0) {
            return;
        }

        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'processing_attempt_count' => new \Zend_Db_Expr('processing_attempt_count + ' . $increment),
                'processing_attempt_date' => $this->getHelper('Data')->getCurrentGmtDate()
            ],
            [
                'id IN (?)' => $ids
            ]
        );
    }

    //########################################
}
