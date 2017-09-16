<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization;

class Orders extends AbstractModel
{
    //########################################

    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::ORDERS;
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

        $result = !$this->processTask('Orders\Reserve\Cancellation') ? false : $result;
        $result = !$this->processTask('Orders\Receive') ? false : $result;
        $result = !$this->processTask('Orders\Receive\Details') ? false : $result;
        $result = !$this->processTask('Orders\Refund') ? false : $result;
        $result = !$this->processTask('Orders\Cancel') ? false : $result;
        $result = !$this->processTask('Orders\Update') ? false : $result;

        return $result;
    }

    //########################################
}