<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Processing\Action;

class Item extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //####################################

    /** @var \Ess\M2ePro\Model\Amazon\Processing\Action $action */
    private $action = null;

    /** @var \Ess\M2ePro\Model\Request\Pending\Single $requestPendingSingle */
    private $requestPendingSingle = null;

    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Processing\Action\Item');
    }

    //####################################

    public function setAction(\Ess\M2ePro\Model\Amazon\Processing\Action $action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Processing\Action
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAction()
    {
        if (!$this->getId()) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Instance must be loaded first.');
        }

        if (!is_null($this->action)) {
            return $this->action;
        }

        return $this->action = $this->activeRecordFactory->getObject('Amazon\Processing\Action', $this->getActionId());
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

        if (!is_null($this->requestPendingSingle)) {
            return $this->requestPendingSingle;
        }

        return $this->requestPendingSingle = $this->activeRecordFactory->getObject(
            'Request\Pending\Single', $this->getRequestPendingSingleId()
        );
    }

    //####################################

    public function getActionId()
    {
        return (int)$this->getData('action_id');
    }

    public function getRequestPendingSingleId()
    {
        return (int)$this->getData('request_pending_single_id');
    }

    public function getRelatedId()
    {
        return (int)$this->getData('related_id');
    }

    public function getInputData()
    {
        return $this->getSettings('input_data');
    }

    public function getOutputData()
    {
        return $this->getSettings('output_data');
    }

    public function getOutputMessages()
    {
        return $this->getSettings('output_messages');
    }

    public function getAttemptsCount()
    {
        return (int)$this->getData('attempts_count');
    }

    public function isCompleted()
    {
        return (bool)$this->getData('is_completed');
    }

    public function isSkipped()
    {
        return (bool)$this->getData('is_skipped');
    }

    //####################################
}