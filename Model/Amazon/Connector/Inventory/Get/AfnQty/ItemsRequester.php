<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\AfnQty;

class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function getCommand(): array
    {
        return ['inventory', 'get', 'afnQty'];
    }
}
