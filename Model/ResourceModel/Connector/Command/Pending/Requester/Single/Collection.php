<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Connector\Command\Pending\Requester\Single;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractCollection
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
        $mpsrTable = $this->activeRecordFactory->getObject('Request\Pending\Single')->getResource()->getMainTable();

        $this->getSelect()->joinLeft(
            array('mpsr' => $mpsrTable),
            'main_table.request_pending_single_id = mpsr.id', array()
        );

        $this->addFieldToFilter('mpsr.is_completed', 1);
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