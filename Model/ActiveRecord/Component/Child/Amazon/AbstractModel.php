<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon;

/**
 * Class AbstractModel
 * @package Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\AbstractModel
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    //########################################
}
