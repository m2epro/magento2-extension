<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\General;

/**
 * Class \Ess\M2ePro\Model\Ebay\Synchronization\General\AccountPickupStore
 */
class AccountPickupStore extends AbstractModel
{
    //########################################

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
        return 60;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 100;
    }

    //########################################

    public function performActions()
    {
        $result = true;

        $result = !$this->processTask('AccountPickupStore\Process') ? false : $result;
        $result = !$this->processTask('AccountPickupStore\Update') ? false : $result;

        return $result;
    }

    //########################################
}
