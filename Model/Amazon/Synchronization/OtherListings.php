<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization;

class OtherListings extends AbstractModel
{
    //########################################

    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::OTHER_LISTINGS;
    }

    protected function getNick()
    {
        return NULL;
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

        $result = !$this->processTask('OtherListings\Update\Blocked') ? false : $result;
        $result = !$this->processTask('OtherListings\Update') ? false : $result;
        $result = !$this->processTask('OtherListings\Title') ? false : $result;

        return $result;
    }

    //########################################
}