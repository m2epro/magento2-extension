<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Command\Pending\Requester;

class Single extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Processing $processing */
    private $processing = null;

    /** @var \Ess\M2ePro\Model\Request\Pending\Single $requestPendingSingle */
    private $requestPendingSingle = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Connector\Command\Pending\Requester\Single');
    }

    //########################################

    public function getProcessing()
    {
        if (!is_null($this->processing)) {
            return $this->processing;
        }

        return $this->processing = $this->activeRecordFactory->getObjectLoaded(
            'Processing', $this->getProcessingId(), NULL, false
        );
    }

    public function getRequestPendingSingle()
    {
        if (!is_null($this->requestPendingSingle)) {
            return $this->requestPendingSingle;
        }

        return $this->requestPendingSingle = $this->activeRecordFactory->getObjectLoaded(
            'Request\Pending\Single', $this->getRequestPendingSingleId(), NULL, false
        );
    }

    //########################################

    public function getProcessingId()
    {
        return (int)$this->getData('processing_id');
    }

    public function getRequestPendingSingleId()
    {
        return (int)$this->getData('request_pending_single_id');
    }

    //########################################
}