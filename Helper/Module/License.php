<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

/**
 * Class \Ess\M2ePro\Helper\Module\License
 */
class License extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $modelFactory;
    protected $primaryConfig;
    protected $country;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Config\Manager\Primary $primaryConfig,
        \Magento\Config\Model\Config\Source\Locale\Country $country,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->modelFactory = $modelFactory;
        $this->primaryConfig = $primaryConfig;
        $this->country = $country;

        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getKey()
    {
        return (string)$this->primaryConfig->getGroupValue('/license/', 'key');
    }

    // ---------------------------------------

    public function getStatus()
    {
        return (bool)$this->primaryConfig->getGroupValue('/license/', 'status');
    }

    // ---------------------------------------

    public function getDomain()
    {
        return (string)$this->primaryConfig->getGroupValue('/license/', 'domain');
    }

    public function getIp()
    {
        return (string)$this->primaryConfig->getGroupValue('/license/', 'ip');
    }

    // ---------------------------------------

    public function getEmail()
    {
        return (string)$this->primaryConfig->getGroupValue('/license/info/', 'email');
    }

    // ---------------------------------------

    public function isValidDomain()
    {
        $isValid = $this->primaryConfig->getGroupValue('/license/valid/', 'domain');
        return $isValid === null || (bool)$isValid;
    }

    public function isValidIp()
    {
        $isValid = $this->primaryConfig->getGroupValue('/license/valid/', 'ip');
        return $isValid === null || (bool)$isValid;
    }

    //########################################

    public function obtainRecord(
        $email = null,
        $firstName = null,
        $lastName = null,
        $country = null,
        $city = null,
        $postalCode = null,
        $phone = null
    ) {
        $requestParams = [
            'domain' => $this->getHelper('Client')->getDomain(),
            'directory' => $this->getHelper('Client')->getBaseDirectory()
        ];

        $email !== null && $requestParams['email'] = $email;
        $firstName !== null && $requestParams['first_name'] = $firstName;
        $lastName !== null && $requestParams['last_name'] = $lastName;
        $phone !== null && $requestParams['phone'] = $phone;
        $country !== null && $requestParams['country'] = $country;
        $city !== null && $requestParams['city'] = $city;
        $postalCode !== null && $requestParams['postal_code'] = $postalCode;

        try {
            $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector('license', 'add', 'record', $requestParams);
            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();
        } catch (\Exception $e) {
            return false;
        }

        if (!isset($response['key'])) {
            return false;
        }

        $this->primaryConfig->setGroupValue('/license/', 'key', (string)$response['key']);

        $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
            $this->modelFactory->getObject('Servicing_Task_License')->getPublicNick()
        );

        return true;
    }

    public function setTrial($component)
    {
        if ($this->getKey() === '') {
            return false;
        }

        if (!$this->isNoneMode($component)) {
            return true;
        }

        try {
            $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'license',
                'set',
                'trial',
                ['key' => $this->getKey(),
                'component' => $component]
            );
            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();
        } catch (\Exception $exception) {
            return false;
        }

        if (!isset($response['status']) || !$response['status']) {
            return false;
        }

        $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
            $this->modelFactory->getObject('Servicing_Task_License')->getPublicNick()
        );

        return true;
    }

    //########################################
}
