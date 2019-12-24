<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Connector\Command\Pending\Requester\Single;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Connector\Command\Pending\Requester\Single\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    // ########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            'Ess\M2ePro\Model\Connector\Command\Pending\Requester\Single',
            'Ess\M2ePro\Model\ResourceModel\Connector\Command\Pending\Requester\Single'
        );
    }

    // ########################################

    public function setCompletedRequestPendingSingleFilter()
    {
        $mpsrTable = $this->activeRecordFactory->getObject('Request_Pending_Single')->getResource()->getMainTable();

        $this->getSelect()->joinLeft(
            ['mpsr' => $mpsrTable],
            'main_table.request_pending_single_id = mpsr.id',
            []
        );

        $this->addFieldToFilter('mpsr.is_completed', 1);
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
