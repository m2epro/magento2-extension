<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector;

class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    protected $nameBuilder;
    protected $ebayFactory;

    //####################################

    public function __construct(
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->nameBuilder = $nameBuilder;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //####################################

    public function getConnector($entity, $type, $name, array $params = array(), $marketplace = NULL, $account = NULL)
    {
        $classParts = ['Ebay\Connector'];

        !empty($entity) && $classParts[] = $entity;
        !empty($type)   && $classParts[] = $type;
        !empty($name)   && $classParts[] = $name;

        $className = $this->nameBuilder->buildClassName($classParts);

        $connectorObjectData = ['params' => $params];
        if (is_int($marketplace) || is_string($marketplace)) {
            $connectorObjectData['marketplace'] = $this->ebayFactory->getCachedObjectLoaded(
                'Marketplace',(int)$marketplace
            );
        }

        if ($account instanceof \Ess\M2ePro\Model\Account) {
            $connectorObjectData['account'] = $account;
        } elseif (is_int($account) || is_string($account)) {
            $connectorObjectData['account'] = $this->ebayFactory->getCachedObjectLoaded(
                'Account',(int)$account
            );
        }

        /** @var \Ess\M2ePro\Model\Connector\Command\AbstractModel $connectorObject */
        $connectorObject = $this->modelFactory->getObject($className, $connectorObjectData);
        $connectorObject->setProtocol($this->getProtocol());

        return $connectorObject;
    }

    public function getCustomConnector($modelName, array $params = array(), $marketplace = NULL, $account = NULL)
    {
        $connectorObjectData = ['params' => $params];
        if (is_int($marketplace) || is_string($marketplace)) {
            $connectorObjectData['marketplace'] = $this->ebayFactory->getCachedObjectLoaded(
                'Marketplace',(int)$marketplace
            );
        }

        if ($account instanceof \Ess\M2ePro\Model\Account) {
            $connectorObjectData['account'] = $account;
        } elseif (is_int($account) || is_string($account)) {
            $connectorObjectData['account'] = $this->ebayFactory->getCachedObjectLoaded(
                'Account',(int)$account
            );
        }

        /** @var \Ess\M2ePro\Model\Connector\Command\AbstractModel $connectorObject */
        $connectorObject = $this->modelFactory->getObject($modelName, $connectorObjectData);
        $connectorObject->setProtocol($this->getProtocol());

        return $connectorObject;
    }

    public function getVirtualConnector($entity, $type, $name,
                                        array $requestData = array(), $responseDataKey = NULL,
                                        $marketplace = NULL, $account = NULL, $requestTimeOut = NULL)
    {
        return $this->getCustomVirtualConnector(
            'Connector\Command\RealTime\Virtual',
            $entity, $type, $name,
            $requestData, $responseDataKey, $marketplace, $account,
            $requestTimeOut
        );
    }

    public function getCustomVirtualConnector($modelName, $entity, $type, $name,
                                              array $requestData = array(), $responseDataKey = NULL,
                                              $marketplace = NULL, $account = NULL, $requestTimeOut = NULL)
    {
        /** @var \Ess\M2ePro\Model\Connector\Command\RealTime\Virtual $virtualConnector */
        $virtualConnector = $this->modelFactory->getObject($modelName);
        $virtualConnector->setProtocol($this->getProtocol());
        $virtualConnector->setCommand(array($entity, $type, $name));
        $virtualConnector->setResponseDataKey($responseDataKey);
        !is_null($requestTimeOut) && $virtualConnector->setRequestTimeOut($requestTimeOut);

        if (is_int($marketplace) || is_string($marketplace)) {
            $marketplace = $this->ebayFactory->getCachedObjectLoaded(
                'Marketplace',(int)$marketplace
            );
        }

        if (is_int($account) || is_string($account)) {
            $account = $this->ebayFactory->getCachedObjectLoaded(
                'Account',(int)$account
            );
        }

        if ($marketplace instanceof \Ess\M2ePro\Model\Marketplace) {
            $requestData['marketplace'] = $marketplace->getNativeId();
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
        return $this->modelFactory->getObject('Ebay\Connector\Protocol');
    }

    //####################################
}