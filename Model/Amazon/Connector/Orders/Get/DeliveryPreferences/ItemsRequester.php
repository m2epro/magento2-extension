<?php

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\DeliveryPreferences;

abstract class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    public function getRequestData(): array
    {
        return $this->params;
    }

    public function getCommand(): array
    {
        return ['orders', 'get', 'deliveryPreferences'];
    }
}
