<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Config;

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