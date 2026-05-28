<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Amazon\Connector\InventoryLocation\Get\Locations;

class Processor
{
    private \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $dispatcherFactory;

    public function __construct(\Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory $dispatcherFactory)
    {
        $this->dispatcherFactory = $dispatcherFactory;
    }

    /**
     * @param \Ess\M2ePro\Model\Account $account
     *
     * @return \Ess\M2ePro\Model\Amazon\Connector\InventoryLocation\Get\Locations\Location[]
     */
    public function process(\Ess\M2ePro\Model\Account $account): array
    {
        $dispatcher = $this->dispatcherFactory->create();

        $command = $dispatcher->getConnectorByClass(
            Command::class,
            [],
            $account
        );

        $dispatcher->process($command);

        $raeResponse = $command->getResponseData();

        $locations = [];
        foreach ($raeResponse['locations'] ?? [] as $rawLocation) {
            $locations[] = new Location(
                $rawLocation['alias'] ?: '',
                $rawLocation['supply_source_id'],
                $rawLocation['supply_source_code']
            );
        }

        return $locations;
    }
}
