<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action;

class Item extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractDb
{
    // ########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_processing_action_item', 'id');
    }

    // ########################################

    public function incrementAttemptsCount(array $itemIds)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            array(
                'attempts_count' => new \Zend_Db_Expr('attempts_count + 1'),
            ),
            array('id IN (?)' => $itemIds)
        );
    }

    public function markAsInProgress(array $itemIds, \Ess\M2ePro\Model\Request\Pending\Single $requestPendingSingle)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            array(
                'request_pending_single_id' => $requestPendingSingle->getId(),
                'is_completed'              => 0,
            ),
            array('id IN (?)' => $itemIds)
        );
    }

    public function markAsSkippedProductAction(array $relatedIds)
    {
        $allowedActionTypes = array(
           \Ess\M2ePro\Model\Amazon\Processing\Action::TYPE_PRODUCT_ADD,
           \Ess\M2ePro\Model\Amazon\Processing\Action::TYPE_PRODUCT_UPDATE,
           \Ess\M2ePro\Model\Amazon\Processing\Action::TYPE_PRODUCT_DELETE,
        );

        $apaTable = $this->activeRecordFactory->getObject('Amazon\Processing\Action')->getResource()->getMainTable();

        $allowedActionsSelect = $this->getConnection()->select()
            ->from($apaTable, 'id')
            ->where('type IN (?)', $allowedActionTypes);

        $this->getConnection()->update(
            $this->getMainTable(),
            array('is_skipped' => 1),
            array(
                'action_id IN (?)'  => $allowedActionsSelect,
                'related_id IN (?)' => $relatedIds,
                'request_pending_single_id IS NULL',
                'is_completed = ?'  => 0,
            )
        );
    }

    public function getUniqueRequestPendingSingleIds()
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable(), new \Zend_Db_Expr('DISTINCT `request_pending_single_id`'))
            ->where('is_completed = ?', 0)
            ->distinct(true);

        return $this->getConnection()->fetchCol($select);
    }

    public function deleteByAction(\Ess\M2ePro\Model\Amazon\Processing\Action $action)
    {
        return $this->getConnection()->delete($this->getMainTable(), array('action_id = ?' => $action->getId()));
    }

    // ########################################
}