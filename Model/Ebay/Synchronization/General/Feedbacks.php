<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\General;

class Feedbacks extends AbstractModel
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
        return 0;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 60;
    }

    //########################################

    protected function performActions()
    {
        $result = true;

        $result = !$this->processTask('Feedbacks\Receive') ? false : $result;
        $result = !$this->processTask('Feedbacks\Response') ? false : $result;

        return $result;
    }

    //########################################
}