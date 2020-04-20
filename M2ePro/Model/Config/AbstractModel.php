<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Config;

/**
 * Class \Ess\M2ePro\Model\Config\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
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

    public function getNotice()
    {
        return $this->getData('notice');
    }

    //########################################
}
