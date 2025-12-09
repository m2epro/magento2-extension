<?php

namespace Ess\M2ePro\Model\Walmart\Connector\Repricer\Get\Strategies;

class Processor
{
    private \Ess\M2ePro\Model\Walmart\Connector\DispatcherFactory $dispatcherFactory;

    public function __construct(\Ess\M2ePro\Model\Walmart\Connector\DispatcherFactory $dispatcherFactory)
    {
        $this->dispatcherFactory = $dispatcherFactory;
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Connector\Repricer\Get\Strategies\StrategyEntity[]
     * @throws \Ess\M2ePro\Model\Exception
     */
    public function process(\Ess\M2ePro\Model\Account $account): array
    {
        $dispatcher = $this->dispatcherFactory->create();
        /** @var Command $command */
        $command = $dispatcher->getConnectorByClass(
            Command::class,
            [],
            $account
        );

        $command->process();

        return $command->getResponseData();
    }
}
