<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\Details;

abstract class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    // ########################################

    public function getCommand()
    {
        return array('orders','get','entitiesDetails');
    }

    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Connector\Command\Pending\Processing\Runner\Partial';
    }

    // ########################################

    protected function getResponserParams()
    {
        return array(
            'account_id'     => $this->account->getId(),
            'marketplace_id' => $this->account->getChildObject()->getMarketplaceId()
        );
    }

    // ########################################

    protected function getRequestData()
    {
        return array(
            'items' => $this->params['items'],
        );
    }

    // ########################################
}