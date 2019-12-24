<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\Details;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Orders\Get\Details\ItemsRequester
 */
abstract class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    // ########################################

    public function getCommand()
    {
        return ['orders','get','entitiesDetails'];
    }

    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Connector_Command_Pending_Processing_Runner_Partial';
    }

    // ########################################

    protected function getResponserParams()
    {
        return [
            'account_id'     => $this->account->getId(),
            'marketplace_id' => $this->account->getChildObject()->getMarketplaceId()
        ];
    }

    // ########################################

    protected function getRequestData()
    {
        return [
            'items' => $this->params['items'],
        ];
    }

    // ########################################
}
