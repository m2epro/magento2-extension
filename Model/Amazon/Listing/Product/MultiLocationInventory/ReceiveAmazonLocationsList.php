<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Listing\Product\MultiLocationInventory;

class ReceiveAmazonLocationsList
{
    private \Ess\M2ePro\Model\Amazon\Connector\InventoryLocation\Get\Locations\Processor $locationProcessor;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Connector\InventoryLocation\Get\Locations\Processor $locationProcessor
    ) {
        $this->locationProcessor = $locationProcessor;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Connector\InventoryLocation\Get\Locations\Location[]
     */
    public function execute(\Ess\M2ePro\Model\Account $account): array
    {
        return $this->locationProcessor->process($account);
    }
}
