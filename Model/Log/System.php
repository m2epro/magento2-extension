<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Log;

/**
 * Class \Ess\M2ePro\Model\Log\System
 */
class System extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    const TYPE_LOGGER              = 100;
    const TYPE_EXCEPTION           = 200;
    const TYPE_EXCEPTION_CONNECTOR = 201;
    const TYPE_FATAL_ERROR         = 300;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Log\System');
    }

    //########################################

    public function getType()
    {
        return $this->getData('type');
    }

    public function getClass()
    {
        return $this->getData('class');
    }

    public function getDescription()
    {
        return $this->getData('description');
    }

    public function getDetailedDescription()
    {
        return $this->getData('detailed_description');
    }

    public function getAdditionalData()
    {
        return $this->getData('additional_data');
    }

    //########################################
}
