<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Processing;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action
 */
class Action extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    // ########################################

    public function _construct()
    {
        $this->_init('m2epro_amazon_processing_action', 'id');
    }

    // ########################################

    public function markAsInProgress(array $itemIds, \Ess\M2ePro\Model\Request\Pending\Single $requestPendingSingle)
    {
        $this->getConnection()->update(
            $this->getMainTable(),
            [
                'request_pending_single_id' => $requestPendingSingle->getId(),
            ],
            ['id IN (?)' => $itemIds]
        );
    }

    public function getUniqueRequestPendingSingleIds()
    {
        $select = $this->getConnection()
            ->select()
            ->from($this->getMainTable(), new \Zend_Db_Expr('DISTINCT `request_pending_single_id`'))
            ->where('request_pending_single_id IS NOT NULL')
            ->distinct(true);

        return $this->getConnection()->fetchCol($select);
    }

    // ########################################
}
