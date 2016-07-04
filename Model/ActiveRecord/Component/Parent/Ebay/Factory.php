<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay;

class Factory extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractFactory
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Ebay::NICK;
    }

    //########################################
}
