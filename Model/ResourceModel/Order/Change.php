<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Order;

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
            array(
                'id IN(?)' => $ids
            )
        );
    }

    public function deleteByOrderAction($orderId, $action)
    {
        $this->getConnection()->delete(
            $this->getMainTable(),
            array(
                'order_id = ?' => $orderId,
                'action = ?' => $action
            )
        );
    }

    public function deleteByProcessingAttemptCount($count = 3, $component = NULL)
    {
        $count = (int)$count;

        if ($count <= 0) {
            return;
        }

        $where = array(
            'processing_attempt_count >= ?' => $count
        );

        if (!is_null($component)) {
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
            array(
                'processing_attempt_count' => new \Zend_Db_Expr('processing_attempt_count + ' . $increment),
                'processing_attempt_date' => $this->getHelper('Data')->getCurrentGmtDate()
            ),
            array(
                'id IN (?)' => $ids
            )
        );
    }

    //########################################
}