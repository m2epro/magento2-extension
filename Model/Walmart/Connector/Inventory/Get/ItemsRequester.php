<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Inventory\Get;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Inventory\Get\ItemsRequester
 */
abstract class ItemsRequester extends \Ess\M2ePro\Model\Walmart\Connector\Command\Pending\Requester
{
    // ########################################

    public function getRequestData()
    {
        return [];
    }

    public function getCommand()
    {
        return ['inventory', 'get', 'items'];
    }

    // ########################################
}
