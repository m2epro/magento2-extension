<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Inventory\Get;

class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    // ########################################

    public function getRequestData()
    {
        $requestData = array();
        if (isset($this->params['full_items_data'])) {
            $requestData['full_items_data'] = $this->params['full_items_data'];
        }

        return $requestData;
    }

    public function getCommand()
    {
        return array('inventory','get','items');
    }

    // ########################################
}