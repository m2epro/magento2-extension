<?php

namespace Ess\M2ePro\Model\Walmart\Connector;

class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    private \Magento\Framework\Code\NameBuilder $nameBuilder;
    private \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory;
    private \Ess\M2ePro\Model\Walmart\Connector\Protocol $protocol;
    private \Magento\Framework\ObjectManagerInterface $objectManager;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Connector\Protocol $protocol,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        parent::__construct($helperFactory, $modelFactory);

        $this->nameBuilder = $nameBuilder;
        $this->walmartFactory = $walmartFactory;
        $this->protocol = $protocol;
        $this->objectManager = $objectManager;
    }

    // ----------------------------------------

    public function getConnector($entity, $type, $name, array $params = [], $account = null)
    {
        $classParts = ['Walmart\Connector'];

        !empty($entity) && $classParts[] = $entity;
        !empty($type) && $classParts[] = $type;
        !empty($name) && $classParts[] = $name;

        $className = $this->nameBuilder->buildClassName($classParts);

        if (is_int($account) || is_string($account)) {
            $account = $this->walmartFactory->getCachedObjectLoaded('Account', (int)$account);
        }

        /** @var \Ess\M2ePro\Model\Connector\Command\AbstractModel $connectorObject */
        $connectorObject = $this->modelFactory->getObject($className, [
            'params' => $params,
            'account' => $account,
        ]);
        $connectorObject->setProtocol($this->protocol);

        return $connectorObject;
    }

    public function getCustomConnector($modelName, array $params = [], $account = null)
    {
        if (is_int($account) || is_string($account)) {
            $account = $this->walmartFactory->getCachedObjectLoaded('Account', (int)$account);
        }

        /** @var \Ess\M2ePro\Model\Connector\Command\AbstractModel $connectorObject */
        $connectorObject = $this->modelFactory->getObject($modelName, [
            'params' => $params,
            'account' => $account,
        ]);
        $connectorObject->setProtocol($this->protocol);

        return $connectorObject;
    }

    public function getConnectorByClass(
        string $className,
        array $params = [],
        $account = null
    ): \Ess\M2ePro\Model\Connector\Command\AbstractModel {
        if (is_int($account) || is_string($account)) {
            $account = $this->walmartFactory->getCachedObjectLoaded(
                'Account',
                (int)$account
            );
        }

        /** @var \Ess\M2ePro\Model\Connector\Command\AbstractModel $connectorObject */
        $connectorObject = $this->objectManager->create(
            $className,
            [
                'params' => $params,
                'account' => $account,
            ]
        );
        $connectorObject->setProtocol($this->protocol);

        return $connectorObject;
    }

    /**
     * @param string $entity
     * @param string $type
     * @param string $name
     * @param array $requestData
     * @param string|null $responseDataKey
     * @param null|int|\Ess\M2ePro\Model\Account $account
     *
     * @return \Ess\M2ePro\Model\Connector\Command\RealTime\Virtual
     */
    public function getVirtualConnector(
        $entity,
        $type,
        $name,
        array $requestData = [],
        $responseDataKey = null,
        $account = null
    ) {
        return $this->getCustomVirtualConnector(
            'Connector_Command_RealTime_Virtual',
            $entity,
            $type,
            $name,
            $requestData,
            $responseDataKey,
            $account
        );
    }

    public function getCustomVirtualConnector(
        $modelName,
        $entity,
        $type,
        $name,
        array $requestData = [],
        $responseDataKey = null,
        $account = null
    ) {
        /** @var \Ess\M2ePro\Model\Connector\Command\RealTime\Virtual $virtualConnector */
        $virtualConnector = $this->modelFactory->getObject($modelName);
        $virtualConnector->setProtocol($this->protocol);
        $virtualConnector->setCommand([$entity, $type, $name]);
        $virtualConnector->setResponseDataKey($responseDataKey);

        if (is_int($account) || is_string($account)) {
            $account = $this->walmartFactory->getCachedObjectLoaded('Account', (int)$account);
        }

        if ($account instanceof \Ess\M2ePro\Model\Account) {
            $requestData['account'] = $account->getChildObject()->getServerHash();
        }

        $virtualConnector->setRequestData($requestData);

        return $virtualConnector;
    }

    // ----------------------------------------

    public function process(\Ess\M2ePro\Model\Connector\Command\AbstractModel $connector)
    {
        $connector->process();
    }
}
