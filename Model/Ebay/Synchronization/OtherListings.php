<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization;

class OtherListings extends \Ess\M2ePro\Model\Ebay\Synchronization\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::OTHER_LISTINGS;
    }

    /**
     * @return null
     */
    protected function getNick()
    {
        return NULL;
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return '3rd Party Listings';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 0;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    protected function performActions()
    {
        $result = true;

        $result = !$this->processTask('OtherListings\Update') ? false : $result;
        $result = !$this->processTask('OtherListings\Sku') ? false : $result;

        return $result;
    }

    //########################################
}