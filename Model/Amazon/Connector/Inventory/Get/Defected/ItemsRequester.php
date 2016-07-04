<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Defected;

class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    // ########################################

    public function getRequestData()
    {
        return array();
    }

    public function getCommand()
    {
        return array('inventory','get','defectedItems');
    }

    // ########################################
}