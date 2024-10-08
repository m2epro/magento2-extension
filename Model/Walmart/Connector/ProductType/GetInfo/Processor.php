<?php

namespace Ess\M2ePro\Model\Walmart\Connector\ProductType\GetInfo;

class Processor
{
    private \Ess\M2ePro\Model\Walmart\Connector\DispatcherFactory $dispatcherFactory;

    public function __construct(\Ess\M2ePro\Model\Walmart\Connector\DispatcherFactory $dispatcherFactory)
    {
        $this->dispatcherFactory = $dispatcherFactory;
    }

    public function process(string $productTypeNick, \Ess\M2ePro\Model\Marketplace $marketplace): Response
    {
        $dispatcher = $this->dispatcherFactory->create();
        /** @var Command $command */
        $command = $dispatcher->getConnectorByClass(
            Command::class,
            [
                Command::PARAM_KEY_MARKETPLACE_ID => $marketplace->getNativeId(),
                Command::PARAM_KEY_PRODUCT_TYPE_NICK => $productTypeNick,
            ]
        );

        $command->process();

        /** @var Response */
        return $command->getResponseData();
    }
}
