<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Connector\Command\Pending\Requester\Partial;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Connector\Command\Pending\Requester\Partial\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Connector\Command\Pending\Requester\Partial',
            'Ess\M2ePro\Model\ResourceModel\Connector\Command\Pending\Requester\Partial'
        );
    }

    // ########################################

    public function setCompletedRequestPendingPartialFilter()
    {
        $mpprTable = $this->activeRecordFactory->getObject('Request_Pending_Partial')->getResource()->getMainTable();

        $this->getSelect()->joinLeft(
            ['mppr' => $mpprTable],
            'main_table.request_pending_partial_id = mppr.id',
            []
        );

        $this->addFieldToFilter('mppr.is_completed', 1);
    }

    public function setNotCompletedProcessingFilter()
    {
        $mpTable = $this->activeRecordFactory->getObject('Processing')->getResource()->getMainTable();

        $this->getSelect()->joinLeft(
            ['mp' => $mpTable],
            'main_table.processing_id = mp.id',
            []
        );

        $this->addFieldToFilter('mp.is_completed', 0);
    }

    // ########################################
}
