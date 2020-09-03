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
    protected $country;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Config\Model\Config\Source\Locale\Country $country,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->modelFactory = $modelFactory;
        $this->country = $country;

        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getKey()
    {
        return (string)$this->getHelper('Module')->getConfig()->getGroupValue('/license/', 'key');
    }

    public function getStatus()
    {
        return (bool)$this->getHelper('Module')->getConfig()->getGroupValue('/license/', 'status');
    }

    public function getDomain()
    {
        return (string)$this->getHelper('Module')->getConfig()->getGroupValue('/license/domain/', 'valid');
    }

    public function getIp()
    {
        return (string)$this->getHelper('Module')->getConfig()->getGroupValue('/license/ip/', 'valid');
    }

    public function getEmail()
    {
        return (string)$this->getHelper('Module')->getConfig()->getGroupValue('/license/info/', 'email');
    }

    public function isValidDomain()
    {
        $isValid = $this->getHelper('Module')->getConfig()->getGroupValue('/license/domain/', 'is_valid');
        return $isValid === null || (bool)$isValid;
    }

    public function isValidIp()
    {
        $isValid = $this->getHelper('Module')->getConfig()->getGroupValue('/license/ip/', 'is_valid');
        return $isValid === null || (bool)$isValid;
    }

    public function getRealDomain()
    {
        return (string)$this->getHelper('Module')->getConfig()->getGroupValue('/license/domain/', 'real');
    }

    public function getRealIp()
    {
        return (string)$this->getHelper('Module')->getConfig()->getGroupValue('/license/ip/', 'real');
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
        if ($this->getHelper('Server_Maintenance')->isNow()) {
            return false;
        }

        $requestParams = [
            'domain'    => $this->getHelper('Client')->getDomain(),
            'directory' => $this->getHelper('Client')->getBaseDirectory()
        ];

        $email !== null && $requestParams['email'] = $email;
        $firstName !== null && $requestParams['first_name'] = $firstName;
        $lastName !== null && $requestParams['last_name'] = $lastName;
        $phone !== null && $requestParams['phone'] = $phone;
        $country !== null && $requestParams['country'] = $country;
        $city !== null && $requestParams['city'] = $city;
        $postalCode !== null && $requestParams['postal_code'] = $postalCode;

        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('license', 'add', 'record', $requestParams);
        $dispatcherObject->process($connectorObj);
        $response = $connectorObj->getResponseData();

        if (!isset($response['key'])) {
            return false;
        }

        $this->getHelper('Module')->getConfig()->setGroupValue('/license/', 'key', (string)$response['key']);

        $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
            $this->modelFactory->getObject('Servicing_Task_License')->getPublicNick()
        );

        return true;
    }

    public function updateLicenseUserInfo(
        $email = null,
        $firstName = null,
        $lastName = null,
        $country = null,
        $city = null,
        $postalCode = null,
        $phone = null
    ) {
        if ($this->getHelper('Server_Maintenance')->isNow()) {
            return false;
        }

        $requestParams['key'] = $this->getKey();

        $data = [
            'email'       => $email,
            'first_name'  => $firstName,
            'last_name'   => $lastName,
            'phone'       => $phone,
            'country'     => $country,
            'city'        => $city,
            'postal_code' => $postalCode
        ];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $requestParams[$key] = $value;
        }

        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('license', 'update', 'record', $requestParams);
        $dispatcherObject->process($connectorObj);

        return true;
    }

    //########################################

    public function getUserInfo()
    {}

    public function getData()
    {
        return [
            'key'        => $this->getKey(),
            'status'     => $this->getStatus(),
            'domain'     => $this->getDomain(),
            'ip'         => $this->getIp(),
            'info'       => [
                'email' => $this->getEmail()
            ],
            'valid'      => [
                'domain' => $this->isValidDomain(),
                'ip'     => $this->isValidIp()
            ],
            'connection' => [
                'domain'    => $this->getRealDomain(),
                'ip'        => $this->getRealIp()
            ]
        ];
    }

    //########################################
}
