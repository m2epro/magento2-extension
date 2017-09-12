<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Amazon\Processing;

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
            array(
                'request_pending_single_id' => $requestPendingSingle->getId(),
            ),
            array('id IN (?)' => $itemIds)
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