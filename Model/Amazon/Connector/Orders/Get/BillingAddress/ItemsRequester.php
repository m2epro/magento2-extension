<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\Orders\Get\BillingAddress;

abstract class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    public function getCommand(): array
    {
        return ['orders', 'get', 'billingAddress'];
    }
}
