<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Actions\Processor\Connector\Multiple;

/**
 * Class \Ess\M2ePro\Model\Ebay\Actions\Processor\Connector\Multiple\Dispatcher
 */
class Dispatcher extends \Ess\M2ePro\Model\Ebay\Connector\Dispatcher
{
    //####################################

    /**
     * @param Command\VirtualWithoutCall[] $connectors
     * @param bool $asynchronous
     */
    public function processMultiple(array $connectors, $asynchronous = false)
    {
        /** @var \Ess\M2ePro\Model\Connector\Connection\Multiple $multipleConnection */
        $multipleConnection = $this->modelFactory->getObject('Connector_Connection_Multiple');
        $multipleConnection->setAsynchronous($asynchronous);

        foreach ($connectors as $key => $connector) {

            /** @var \Ess\M2ePro\Model\Connector\Connection\Multiple\RequestContainer $requestContainer */
            $requestContainer = $this->modelFactory->getObject('Connector_Connection_Multiple_RequestContainer');
            $requestContainer->setRequest($connector->getCommandConnection()->getRequest());
            $requestContainer->setTimeout($connector->getCommandConnection()->getTimeout());

            $multipleConnection->addRequestContainer($key, $requestContainer);
        }

        $multipleConnection->process();

        foreach ($connectors as $key => $connector) {
            $connector->getCommandConnection()->setResponse($multipleConnection->getResponse($key));
            $connector->process();
        }
    }

    //####################################
}
