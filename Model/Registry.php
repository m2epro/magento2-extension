<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

class Registry extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    // ----------------------------------------

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Registry::class);
    }

    // ----------------------------------------

    /**
     * @param string $key
     *
     * @return \Ess\M2ePro\Model\Registry
     */
    public function setKey(string $key): Registry
    {
        return $this->setData('key', $key);
    }

    /**
     * @param mixed $value
     *
     * @return \Ess\M2ePro\Model\Registry
     */
    public function setValue($value): Registry
    {
        return $this->setData('value', $value);
    }

    /**
     * @return array|mixed|null
     */
    public function getValue()
    {
        return $this->getData('value');
    }
}
