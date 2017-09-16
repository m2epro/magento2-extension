<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\General;

class AccountPickupStore extends AbstractModel
{
    //########################################

    /**
     * @return null
     */
    protected function getNick()
    {
        return NULL;
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