<?php

namespace Ess\M2ePro\Model\Amazon\Connector\ProductType\SearchByCriteria;

use Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory;

class Processor
{
    /** @var \Ess\M2ePro\Model\Amazon\Connector\DispatcherFactory */
    private $dispatcherFactory;

    public function __construct(DispatcherFactory $dispatcherFactory)
    {
        $this->dispatcherFactory = $dispatcherFactory;
    }

    public function process(Request $request): Response
    {
        $dispatcher = $this->dispatcherFactory->create();

        /** @var Command $command */
        $command = $dispatcher->getConnectorByClass(
            Command::class,
            [Command::REQUEST_PARAM_KEY => $request]
        );

        $dispatcher->process($command);

        return $command->getPreparedResponse();
    }
}
