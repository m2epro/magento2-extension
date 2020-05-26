<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Order\Action;

/**
 * Class \Ess\M2ePro\Model\Amazon\Order\Action\Processing
 */
class Processing extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    const ACTION_TYPE_UPDATE = 'update';
    const ACTION_TYPE_CANCEL = 'cancel';
    const ACTION_TYPE_REFUND = 'refund';
    const ACTION_TYPE_SEND_INVOICE = 'send_invoice';

    //####################################

    /** @var \Ess\M2ePro\Model\Order $order */
    protected $order = null;

    /** @var \Ess\M2ePro\Model\Processing $processing */
    protected $processing = null;

    /** @var \Ess\M2ePro\Model\Request\Pending\Single $requestPendingSingle */
    protected $requestPendingSingle = null;

    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Order\Action\Processing');
    }

    //####################################

    public function setOrder(\Ess\M2ePro\Model\Order $order)
    {
        $this->order = $order;
        return $this;
    }

    public function getOrder()
    {
        if (!$this->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Instance must be loaded first.');
        }

        if ($this->order !== null) {
            return $this->order;
        }

        return $this->order = $this->activeRecordFactory->getObjectLoaded('Order', $this->getOrderId());
    }

    // ---------------------------------------

    public function setProcessing(\Ess\M2ePro\Model\Processing $processing)
    {
        $this->processing = $processing;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Processing
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getProcessing()
    {
        if (!$this->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Instance must be loaded first.');
        }

        if ($this->processing !== null) {
            return $this->processing;
        }

        return $this->processing = $this->activeRecordFactory->getObjectLoaded('Processing', $this->getProcessingId());
    }

    //------------------------------------

    public function setRequestPendingSingle(\Ess\M2ePro\Model\Request\Pending\Single $requestPendingSingle)
    {
        $this->requestPendingSingle = $requestPendingSingle;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Request\Pending\Single
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRequestPendingSingle()
    {
        if (!$this->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Instance must be loaded first.');
        }

        if (!$this->getRequestPendingSingleId()) {
            return null;
        }

        if ($this->requestPendingSingle !== null) {
            return $this->requestPendingSingle;
        }

        $this->requestPendingSingle = $this->activeRecordFactory->getObjectLoaded(
            'Request_Pending_Single',
            $this->getRequestPendingSingleId()
        );

        return $this->requestPendingSingle;
    }

    //####################################

    public function getOrderId()
    {
        return (int)$this->getData('order_id');
    }

    public function getProcessingId()
    {
        return (int)$this->getData('processing_id');
    }

    public function getRequestPendingSingleId()
    {
        return (int)$this->getData('request_pending_single_id');
    }

    public function getActionType()
    {
        return (int)$this->getData('action_type');
    }

    public function getRequestData()
    {
        return $this->getSettings('request_data');
    }

    //####################################
}
