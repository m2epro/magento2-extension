<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Defected;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Defected\ItemsRequester
 */
class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    // ########################################

    public function getRequestData()
    {
        return [];
    }

    public function getCommand()
    {
        return ['inventory','get','defectedItems'];
    }

    // ########################################
}
