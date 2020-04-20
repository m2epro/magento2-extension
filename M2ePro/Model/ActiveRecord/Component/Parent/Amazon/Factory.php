<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon;

/**
 * Class \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory
 */
class Factory extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractFactory
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    //########################################
}
