<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Request\Pending;

/**
 * @method \Ess\M2ePro\Model\ResourceModel\Request\Pending\Single getResource()
 */
class Single extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Request\Pending\Single');
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

    public function getResultData()
    {
        return $this->getSettings('result_data');
    }

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
}