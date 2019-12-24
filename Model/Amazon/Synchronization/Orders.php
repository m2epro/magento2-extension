<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization;

/**
 * Class \Ess\M2ePro\Model\Amazon\Synchronization\Orders
 */
class Orders extends AbstractModel
{
    //########################################

    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::ORDERS;
    }

    protected function getNick()
    {
        return null;
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

        $result = !$this->processTask('Orders_Reserve_Cancellation') ? false : $result;
        $result = !$this->processTask('Orders\Receive') ? false : $result;
        $result = !$this->processTask('Orders_Receive_Details') ? false : $result;
        $result = !$this->processTask('Orders\CreateFailed') ? false : $result;
        $result = !$this->processTask('Orders\Refund') ? false : $result;
        $result = !$this->processTask('Orders\Cancel') ? false : $result;
        $result = !$this->processTask('Orders\Update') ? false : $result;

        return $result;
    }

    //########################################
}
