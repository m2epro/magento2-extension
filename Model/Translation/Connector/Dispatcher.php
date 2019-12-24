<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Translation\Connector;

/**
 * Class \Ess\M2ePro\Model\Translation\Connector\Dispatcher
 */
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
    ) {
        $this->nameBuilder = $nameBuilder;
        $this->parentFactory = $parentFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //####################################

    public function getConnector($entity, $type, $name, array $params = [], $account = null)
    {
        $classParts = ['Translation\Connector'];

        !empty($entity) && $classParts[] = $entity;
        !empty($type) && $classParts[] = $type;
        !empty($name) && $classParts[] = $name;

        $className = $this->nameBuilder->buildClassName($classParts);

        if (is_int($account) || is_string($account)) {
            $account = $this->parentFactory->getCachedObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
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
            $account = $this->parentFactory->getCachedObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
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

    public function getVirtualConnector(
        $entity,
        $type,
        $name,
        array $requestData = [],
        $responseDataKey = null,
        $account = null
    ) {
        $virtualConnector = $this->modelFactory->getObject('Connector_Command_RealTime_Virtual');
        $virtualConnector->setProtocol($this->getProtocol());
        $virtualConnector->setCommand([$entity, $type, $name]);
        $virtualConnector->setResponseDataKey($responseDataKey);

        if (is_int($account) || is_string($account)) {
            $account = $this->parentFactory->getCachedObjectLoaded(
                \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'Account',
                (int)$account
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
        return $this->modelFactory->getObject('Translation_Connector_Protocol');
    }

    //####################################
}
