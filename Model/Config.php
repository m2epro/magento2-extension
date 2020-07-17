<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

/**
 * Class \Ess\M2ePro\Model\Config
 */
class Config extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //########################################

    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Ess\M2ePro\Model\ResourceModel\Config');
    }

    //########################################

    public function getGroup()
    {
        return $this->getData('group');
    }

    public function getKey()
    {
        return $this->getData('key');
    }

    public function getValue()
    {
        return $this->getData('value');
    }

    //########################################
}
