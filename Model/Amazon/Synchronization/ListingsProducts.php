<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization;

class ListingsProducts extends AbstractModel
{
    //########################################

    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::LISTINGS_PRODUCTS;
    }

    protected function getNick()
    {
        return NULL;
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

        $result = !$this->processTask('ListingsProducts\Update\Defected') ? false : $result;
        $result = !$this->processTask('ListingsProducts\Update\Blocked') ? false : $result;
        $result = !$this->processTask('ListingsProducts\Update') ? false : $result;

        return $result;
    }

    //########################################
}