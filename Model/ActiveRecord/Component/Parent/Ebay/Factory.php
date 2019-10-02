<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay;

/**
 * Class Factory
 * @package Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay
 */
class Factory extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractFactory
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    //########################################
}
