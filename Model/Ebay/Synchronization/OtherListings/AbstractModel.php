<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\OtherListings;

abstract class AbstractModel extends \Ess\M2ePro\Model\Ebay\Synchronization\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::OTHER_LISTINGS;
    }

    protected function processTask($taskPath)
    {
        return parent::processTask('OtherListings\\'.$taskPath);
    }

    //########################################
}