<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Inventory\Get;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\ItemsRequester
 */
class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    // ########################################

    public function getRequestData()
    {
        $requestData = [];
        if (isset($this->params['full_items_data'])) {
            $requestData['full_items_data'] = $this->params['full_items_data'];
        }

        return $requestData;
    }

    public function getCommand()
    {
        return ['inventory','get','items'];
    }

    // ########################################
}
