<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization;

final class Launcher extends \Ess\M2ePro\Model\Ebay\Synchronization\AbstractModel
{
    //########################################

    protected function getType()
    {
        return NULL;
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

        //TODO UNSUPPORTED FEATURE
        //$result = !$this->processTask('General') ? false : $result;
        $result = !$this->processTask('ListingsProducts') ? false : $result;
        $result = !$this->processTask('Orders') ? false : $result;
        $result = !$this->processTask('OtherListings') ? false : $result;
        $result = !$this->processTask('Templates') ? false : $result;
        $result = !$this->processTask('Marketplaces') ? false : $result;

        return $result;
    }

    //########################################
}