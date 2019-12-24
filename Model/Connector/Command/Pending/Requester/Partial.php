<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Command\Pending\Requester;

/**
 * Class \Ess\M2ePro\Model\Connector\Command\Pending\Requester\Partial
 */
class Partial extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Processing $processing */
    private $processing = null;

    /** @var \Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial */
    private $requestPendingPartial = null;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Connector\Command\Pending\Requester\Partial');
    }

    //########################################

    public function getProcessing()
    {
        if ($this->processing !== null) {
            return $this->processing;
        }

        return $this->processing = $this->activeRecordFactory->getObjectLoaded(
            'Processing',
            $this->getProcessingId(),
            null,
            false
        );
    }

    public function getRequestPendingPartial()
    {
        if ($this->requestPendingPartial !== null) {
            return $this->requestPendingPartial;
        }

        return $this->requestPendingPartial = $this->activeRecordFactory->getObjectLoaded(
            'Request_Pending_Partial',
            $this->getRequestPendingPartialId(),
            null,
            false
        );
    }

    //########################################

    public function getProcessingId()
    {
        return (int)$this->getData('processing_id');
    }

    public function getRequestPendingPartialId()
    {
        return (int)$this->getData('request_pending_partial_id');
    }

    //########################################
}
