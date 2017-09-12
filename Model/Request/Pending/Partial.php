<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Request\Pending;

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Request\Pending\Partial getResource()
 */
class Partial extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Request\Pending\Partial');
    }

    //####################################

    public function getComponent()
    {
        return $this->getData('component');
    }

    //------------------------------------

    public function getServerHash()
    {
        return $this->getData('server_hash');
    }

    //------------------------------------

    public function getNextPart()
    {
        return $this->getData('next_part');
    }

    //------------------------------------

    public function getResultMessages()
    {
        return $this->getSettings('result_messages');
    }

    //------------------------------------

    public function getExpirationDate()
    {
        return $this->getData('expiration_date');
    }

    public function isCompleted()
    {
        return (bool)$this->getData('is_completed');
    }

    //####################################

    public function getResultData($partNumber)
    {
        return $this->getResource()->getResultData($this, (int)$partNumber);
    }

    public function addResultData(array $data)
    {
        if (is_null($this->getId())) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Instance must be loaded first.');
        }

        $this->getResource()->addResultData($this, $this->getNextPart(), $data);
        $this->setData('next_part', $this->getNextPart() + 1);
        $this->save();
    }

    //####################################

    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }

        $this->getResource()->deleteResultData($this);

        return true;
    }

    //####################################
}