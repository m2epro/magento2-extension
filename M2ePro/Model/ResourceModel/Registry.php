<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Registry
 */
class Registry extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    //########################################

    public function _construct()
    {
        $this->_init('m2epro_registry', 'id');
    }

    //########################################

    public function loadByKey(\Ess\M2ePro\Model\Registry $object, $key)
    {
        $this->load($object, $key, 'key');
        if (!$object->getId()) {
            $object->setData('key', $key);
        }

        return $object;
    }

    //########################################
}
