<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Dispatcher
 */
class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    protected $nameBuilder;
    protected $walmartFactory;

    //####################################

    public function __construct(
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->nameBuilder = $nameBuilder;
        $this->walmartFactory = $walmartFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //####################################

    public function getConnector($entity, $type, $name, array $params = [], $account = null)
    {
        $classParts = ['Walmart\Connector'];

        !empty($entity) && $classParts[] = $entity;
        !empty($type) && $classParts[] = $type;
        !empty($name) && $classParts[] = $name;

        $className = $this->nameBuilder->buildClassName($classParts);

        if (is_int($account) || is_string($account)) {
            $account = $this->walmartFactory->getCachedObjectLoaded(
                'Account',
                (int)$account
            );
        }

        /** @var \Ess\M2ePro\Model\Connector\Command\AbstractModel $connectorObject */
        $connectorObject = $this->modelFactory->getObject($className, [
            'params' => $params,
            'account' => $account
        ]);
        $connectorObject->setProtocol($this->getProtocol());

        return $connectorObject;
    }

    public function getCustomConnector($modelName, array $params = [], $account = null)
    {
        if (is_int($account) || is_string($account)) {
            $account = $this->walmartFactory->getCachedObjectLoaded(
                'Account',
                (int)$account
            );
        }

        /** @var \Ess\M2ePro\Model\Connector\Command\AbstractModel $connectorObject */
        $connectorObject = $this->modelFactory->getObject($modelName, [
            'params' => $params,
            'account' => $account
        ]);
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
        $virtualConnector->setProtocol($this->getProtocol());
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

    //####################################

    public function process(\Ess\M2ePro\Model\Connector\Command\AbstractModel $connector)
    {
        $connector->process();
    }

    //####################################

    private function getProtocol()
    {
        return $this->modelFactory->getObject('Walmart_Connector_Protocol');
    }

    //####################################
}
