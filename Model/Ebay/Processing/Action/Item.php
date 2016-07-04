<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Processing\Action;

class Item extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //####################################

    /** @var \Ess\M2ePro\Model\Ebay\Processing\Action $action */
    private $action = null;

    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Processing\Action\Item');
    }

    //####################################

    public function setAction(\Ess\M2ePro\Model\Ebay\Processing\Action $action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Ebay\Processing\Action
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

        return $this->action = $this->activeRecordFactory->getObjectLoaded(
            'Ebay\Processing\Action', $this->getActionId()
        );
    }

    //####################################

    public function getActionId()
    {
        return (int)$this->getData('action_id');
    }

    public function getRelatedId()
    {
        return (int)$this->getData('related_id');
    }

    public function getInputData()
    {
        return $this->getSettings('input_data');
    }

    public function isSkipped()
    {
        return (bool)$this->getData('is_skipped');
    }

    //####################################
}