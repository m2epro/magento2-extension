<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get;

class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    // ########################################

    public function getCommand()
    {
        return array('orders','get','items');
    }

    // ########################################

    protected function getResponserParams()
    {
        return array(
            'account_id' => $this->account->getId(),
            'marketplace_id' => $this->account->getChildObject()->getMarketplaceId()
        );
    }

    // ########################################

    protected function getRequestData()
    {
        return array(
            'updated_since_time' => $this->params['from_date'],
            'updated_to_time'    => $this->params['to_date'],
            'status_filter'      => !empty($this->params['status']) ? $this->params['status'] : NULL
        );
    }

    // ########################################
}