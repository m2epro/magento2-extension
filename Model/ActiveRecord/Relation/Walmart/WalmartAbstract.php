<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ActiveRecord\Relation\Walmart;

/**
 * Class \Ess\M2ePro\Model\ActiveRecord\Relation\Walmart\WalmartAbstract
 */
abstract class WalmartAbstract extends \Ess\M2ePro\Model\ActiveRecord\Relation\ChildAbstract
{
    //########################################

    public function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Walmart::NICK;
    }

    //########################################
}
