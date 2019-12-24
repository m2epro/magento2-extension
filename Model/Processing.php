<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

/**
 * Class \Ess\M2ePro\Model\Processing
 */
class Processing extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //####################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Processing');
    }

    //####################################

    public function getModel()
    {
        return $this->getData('model');
    }

    public function getParams()
    {
        return $this->getSettings('params');
    }

    public function getResultData()
    {
        return $this->getSettings('result_data');
    }

    public function getResultMessages()
    {
        return $this->getSettings('result_messages');
    }

    public function isCompleted()
    {
        return (bool)$this->getData('is_completed');
    }

    //####################################
}
