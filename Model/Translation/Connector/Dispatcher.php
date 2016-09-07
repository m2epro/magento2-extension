<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Translation\Connector;

class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    protected $nameBuilder;
    protected $parentFactory;

    //####################################

    public function __construct(
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->nameBuilder = $nameBuilder;
        $this->parentFactory = $parentFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //####################################

    public function getConnector($entity, $type, $name, array $params = array(), $account = NULL)
    {
        $classParts = ['Translation\Connector'];

        !empty($entity) && $classParts[] = $entity;
        !empty($type)   && $classParts[] = $type;
        !empty($name)   && $classParts[] = $name;

        $className = $this->nameBuilder->buildClassName($classParts);

        if (is_int($account) || is_string($account)) {
            $account = $this->parentFactory->getCachedObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK, 'Account',(int)$account
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
            $account = $this->parentFactory->getCachedObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK, 'Account',(int)$account
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

    public function getVirtualConnector($entity, $type, $name,
                                        array $requestData = array(), $responseDataKey = NULL,
                                        $account = NULL)
    {
        $virtualConnector = $this->modelFactory->getObject('Connector\Command\RealTime\Virtual');
        $virtualConnector->setProtocol($this->getProtocol());
        $virtualConnector->setCommand(array($entity, $type, $name));
        $virtualConnector->setResponseDataKey($responseDataKey);

        if (is_int($account) || is_string($account)) {
            $account = $this->parentFactory->getCachedObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK, 'Account', (int)$account
            );
        }

        if ($account instanceof \Ess\M2ePro\Model\Account) {
            $requestData['account'] = $account->getChildObject()->getTranslationHash();
        }

        $virtualConnector->setRequestData($requestData);

        return $virtualConnector;
    }

    //####################################

    public function process(\Ess\M2ePro\Model\Connector\Command\AbstractModel $connector)
    {
        return $connector->process();
    }

    //####################################

    private function getProtocol()
    {
        return $this->modelFactory->getObject('Translation\Connector\Protocol');
    }

    //####################################
}