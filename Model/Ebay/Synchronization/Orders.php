<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization;

/**
 * Class \Ess\M2ePro\Model\Ebay\Synchronization\Orders
 */
class Orders extends \Ess\M2ePro\Model\Ebay\Synchronization\AbstractModel
{
    //########################################

    /**
     * @return string
     */
    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::ORDERS;
    }

    /**
     * @return null
     */
    protected function getNick()
    {
        return null;
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

        $result = !$this->processTask('Orders\Cancellation') ? false : $result;
        $result = !$this->processTask('Orders_Reserve_Cancellation') ? false : $result;
        $result = !$this->processTask('Orders\Receive') ? false : $result;
        $result = !$this->processTask('Orders\CreateFailed') ? false : $result;
        $result = !$this->processTask('Orders\Update') ? false : $result;

        return $result;
    }

    //########################################
}
