<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

/**
 * Class \Ess\M2ePro\Model\Registry
 * @method \Ess\M2ePro\Model\ResourceModel\Registry _getResource()
 */
class Registry extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Registry');
    }

    //########################################

    public function getKey()
    {
        return $this->getData('key');
    }

    public function getValue()
    {
        return $this->getData('value');
    }

    // ---------------------------------------

    public function setValue($value)
    {
        is_array($value) && $value = $this->getHelper('Data')->jsonEncode($value);
        return $this->setData('value', $value);
    }

    //########################################

    public function getValueFromJson()
    {
        return $this->getId() === null ?  [] : $this->getHelper('Data')->jsonDecode($this->getValue());
    }

    //########################################

    public function loadByKey($key)
    {
        $this->_getResource()->loadByKey($this, $key);
        return $this;
    }

    //########################################
}
