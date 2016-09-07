<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\M2ePro\Connector;

class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    protected $nameBuilder;

    //####################################

    public function __construct(
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->nameBuilder = $nameBuilder;
        parent::__construct($helperFactory, $modelFactory);
    }

    //####################################

    public function getConnector($entity, $type, $name, array $params = array())
    {
        $classParts = ['M2ePro\Connector'];

        !empty($entity) && $classParts[] = $entity;
        !empty($type)   && $classParts[] = $type;
        !empty($name)   && $classParts[] = $name;

        $className = $this->nameBuilder->buildClassName($classParts);

        /** @var \Ess\M2ePro\Model\Connector\Command\AbstractModel $connectorObject */
        $connectorObject = $this->modelFactory->getObject($className, array(
            'params' => $params,
        ));
        $connectorObject->setProtocol($this->getProtocol());

        return $connectorObject;
    }

    /**
     * @param string $entity
     * @param string $type
     * @param string $name
     * @param array $requestData
     * @param string|null $responseDataKey
     * @return \Ess\M2ePro\Model\Connector\Command\RealTime\Virtual
     */
    public function getVirtualConnector($entity, $type, $name,
                                        array $requestData = array(),
                                        $responseDataKey = NULL)
    {
        $virtualConnector = $this->modelFactory->getObject('Connector\Command\RealTime\Virtual');
        $virtualConnector->setProtocol($this->getProtocol());
        $virtualConnector->setCommand(array($entity, $type, $name));
        $virtualConnector->setResponseDataKey($responseDataKey);

        $virtualConnector->setRequestData($requestData);

        return $virtualConnector;
    }

    //####################################

    public function process(\Ess\M2ePro\Model\Connector\Command\AbstractModel $connector)
    {
        $connector->process();
    }

    //####################################

    private function getProtocol()
    {
        return $this->modelFactory->getObject('M2ePro\Connector\Protocol');
    }

    //####################################
}