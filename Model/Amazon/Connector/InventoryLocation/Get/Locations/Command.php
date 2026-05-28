<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\InventoryLocation\Get\Locations;

class Command extends \Ess\M2ePro\Model\Amazon\Connector\Command\RealTime
{
    protected function getRequestData(): array
    {
        return [];
    }

    protected function getCommand(): array
    {
        return ['inventoryLocation', 'get', 'locations'];
    }
}
