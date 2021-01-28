<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\InvoiceDataReport;

/**
 * Class Ess\M2ePro\Model\Amazon\Connector\Orders\Get\InvoiceDataReport\ItemsRequester
 */
abstract class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    //########################################

    public function getCommand()
    {
        return ['orders','get','invoiceDataReport'];
    }

    //########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Connector_Command_Pending_Processing_Partial_Runner';
    }

    //########################################

    protected function getResponserParams()
    {
        return [
            'account_id'     => $this->account->getId(),
            'marketplace_id' => $this->account->getChildObject()->getMarketplaceId()
        ];
    }

    //########################################

    public function getRequestData()
    {
        return [];
    }

    //########################################
}
