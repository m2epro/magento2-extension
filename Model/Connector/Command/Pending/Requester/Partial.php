<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Command\Pending\Requester;

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
        if (!is_null($this->processing)) {
            return $this->processing;
        }

        return $this->processing = $this->activeRecordFactory->getObjectLoaded(
            'Processing', $this->getProcessingId(), NULL, false
        );
    }

    public function getRequestPendingPartial()
    {
        if (!is_null($this->requestPendingPartial)) {
            return $this->requestPendingPartial;
        }

        return $this->requestPendingPartial = $this->activeRecordFactory->getObjectLoaded(
            'Request\Pending\Partial', $this->getRequestPendingPartialId(), NULL, false
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