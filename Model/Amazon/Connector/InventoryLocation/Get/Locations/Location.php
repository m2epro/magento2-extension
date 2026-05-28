<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\InventoryLocation\Get\Locations;

class Location
{
    public string $alias;
    public string $supplySourceId;
    public string $supplySourceCode;

    public function __construct(
        string $alias,
        string $supplySourceId,
        string $supplySourceCode
    ) {
        $this->alias = $alias;
        $this->supplySourceId = $supplySourceId;
        $this->supplySourceCode = $supplySourceCode;
    }
}
