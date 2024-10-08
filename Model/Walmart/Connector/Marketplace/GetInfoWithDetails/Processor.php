<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\Connector\Marketplace\GetInfoWithDetails;

class Processor
{
    private \Ess\M2ePro\Model\Walmart\Connector\DispatcherFactory $dispatcherFactory;

    public function __construct(\Ess\M2ePro\Model\Walmart\Connector\DispatcherFactory $dispatcherFactory)
    {
        $this->dispatcherFactory = $dispatcherFactory;
    }

    public function process(\Ess\M2ePro\Model\Marketplace $marketplace): Response
    {
        $dispatcher = $this->dispatcherFactory->create();
        /** @var Command $command */
        $command = $dispatcher->getConnectorByClass(
            Command::class,
            [Command::PARAM_KEY_MARKETPLACE_ID => $marketplace->getNativeId()]
        );

        $command->process();

        /** @var Response */
        return $command->getResponseData();
    }
}
