<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector;

class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    protected $nameBuilder;
    protected $amazonFactory;

    //####################################

    public function __construct(
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->nameBuilder = $nameBuilder;
        $this->amazonFactory = $amazonFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //####################################

    public function getConnector($entity, $type, $name, array $params = array(), $account = NULL)
    {
        $classParts = ['Amazon\Connector'];

        !empty($entity) && $classParts[] = $entity;
        !empty($type)   && $classParts[] = $type;
        !empty($name)   && $classParts[] = $name;

        $className = $this->nameBuilder->buildClassName($classParts);

        if (is_int($account) || is_string($account)) {
            $account = $this->amazonFactory->getCachedObjectLoaded(
                'Account',(int)$account
            );
        }

        /** @var \Ess\M2ePro\Model\Connector\Command\AbstractModel $connectorObject */
        $connectorObject = $this->modelFactory->getObject($className, array(
            'params' => $params,
            'account' => $account
        ));
        $connectorObject->setProtocol($this->getProtocol());

        return $connectorObject;
    }

    public function getCustomConnector($modelName, array $params = array(), $account = NULL)
    {
        if (is_int($account) || is_string($account)) {
            $account = $this->amazonFactory->getCachedObjectLoaded(
                'Account', (int)$account
            );
        }

        /** @var \Ess\M2ePro\Model\Connector\Command\AbstractModel $connectorObject */
        $connectorObject = $this->modelFactory->getObject($modelName, array(
            'params' => $params,
            'account' => $account
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
     * @param null|int|\Ess\M2ePro\Model\Account $account
     * @return \Ess\M2ePro\Model\Connector\Command\RealTime\Virtual
     */
    public function getVirtualConnector($entity, $type, $name,
                                        array $requestData = array(),
                                        $responseDataKey = NULL, $account = NULL)
    {
        return $this->getCustomVirtualConnector(
            'Connector\Command\RealTime\Virtual',
            $entity, $type, $name,
            $requestData, $responseDataKey, $account
        );
    }

    public function getCustomVirtualConnector($modelName, $entity, $type, $name,
                                              array $requestData = array(),
                                              $responseDataKey = NULL, $account = NULL)
    {
        /** @var \Ess\M2ePro\Model\Connector\Command\RealTime\Virtual $virtualConnector */
        $virtualConnector = $this->modelFactory->getObject($modelName);
        $virtualConnector->setProtocol($this->getProtocol());
        $virtualConnector->setCommand(array($entity, $type, $name));
        $virtualConnector->setResponseDataKey($responseDataKey);

        if (is_int($account) || is_string($account)) {
            $account = $this->amazonFactory->getCachedObjectLoaded('Account', (int)$account);
        }

        if ($account instanceof \Ess\M2ePro\Model\Account) {
            $requestData['account'] = $account->getChildObject()->getServerHash();
        }

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
        return $this->modelFactory->getObject('Amazon\Connector\Protocol');
    }

    //####################################
}