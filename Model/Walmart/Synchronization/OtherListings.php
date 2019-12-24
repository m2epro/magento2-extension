<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\OtherListings
 */
class OtherListings extends AbstractModel
{
    //########################################

    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::OTHER_LISTINGS;
    }

    protected function getNick()
    {
        return null;
    }

    protected function getTitle()
    {
        return '3rd Party Listings';
    }

    // ---------------------------------------

    protected function getPercentsStart()
    {
        return 0;
    }

    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function performActions()
    {
        $result = true;

        $result = !$this->processTask('OtherListings\Update') ? false : $result;

        return $result;
    }

    //########################################
}
