<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Connector\Command\Pending\Requester\Partial;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractCollection
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
        $mpprTable = $this->activeRecordFactory->getObject('Request\Pending\Partial')->getResource()->getMainTable();

        $this->getSelect()->joinLeft(
            array('mppr' => $mpprTable),
            'main_table.request_pending_partial_id = mppr.id', array()
        );

        $this->addFieldToFilter('mppr.is_completed', 1);
    }

    public function setNotCompletedProcessingFilter()
    {
        $mpTable = $this->activeRecordFactory->getObject('Processing')->getResource()->getMainTable();

        $this->getSelect()->joinLeft(
            array('mp' => $mpTable),
            'main_table.processing_id = mp.id', array()
        );

        $this->addFieldToFilter('mp.is_completed', 0);
    }

    // ########################################
}