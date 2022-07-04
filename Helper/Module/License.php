<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class License
{
    /** @var \Ess\M2ePro\Model\Factory */
    private $modelFactory;
    /** @var \Ess\M2ePro\Helper\Client */
    private $clientHelper;
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $config;

    /**
     * @param \Ess\M2ePro\Helper\Client $clientHelper
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Model\Config\Manager $config
     */
    public function __construct(
        \Ess\M2ePro\Helper\Client $clientHelper,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Config\Manager $config
    ) {
        $this->modelFactory = $modelFactory;
        $this->clientHelper = $clientHelper;
        $this->config = $config;
    }

    // ----------------------------------------

    public function getKey()
    {
        return (string)$this->config->getGroupValue('/license/', 'key');
    }

    public function getStatus()
    {
        return (bool)$this->config->getGroupValue('/license/', 'status');
    }

    public function getDomain()
    {
        return (string)$this->config->getGroupValue('/license/domain/', 'valid');
    }

    public function getIp()
    {
        return (string)$this->config->getGroupValue('/license/ip/', 'valid');
    }

    public function getEmail()
    {
        return (string)$this->config->getGroupValue('/license/info/', 'email');
    }

    public function isValidDomain()
    {
        $isValid = $this->config->getGroupValue('/license/domain/', 'is_valid');

        return $isValid === null || (bool)$isValid;
    }

    public function isValidIp()
    {
        $isValid = $this->config->getGroupValue('/license/ip/', 'is_valid');

        return $isValid === null || (bool)$isValid;
    }

    public function getRealDomain()
    {
        return (string)$this->config->getGroupValue('/license/domain/', 'real');
    }

    public function getRealIp()
    {
        return (string)$this->config->getGroupValue('/license/ip/', 'real');
    }

    // ----------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Registration\Info $info
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function obtainRecord(\Ess\M2ePro\Model\Registration\Info $info)
    {
        $requestParams = [
            'domain'    => $this->clientHelper->getDomain(),
            'directory' => $this->clientHelper->getBaseDirectory(),
        ];

        $requestParams['email'] = $info->getEmail();
        $requestParams['first_name'] = $info->getFirstname();
        $requestParams['last_name'] = $info->getLastname();
        $requestParams['phone'] = $info->getPhone();
        $requestParams['country'] = $info->getCountry();
        $requestParams['city'] = $info->getCity();
        $requestParams['postal_code'] = $info->getPostalCode();

        $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
        $connectorObj = $dispatcherObject->getVirtualConnector('license', 'add', 'record', $requestParams);
        $dispatcherObject->process($connectorObj);
        $response = $connectorObj->getResponseData();

        if (!isset($response['key'])) {
            return false;
        }

        $this->config->setGroupValue('/license/', 'key', (string)$response['key']);

        return true;
    }

    // ----------------------------------------

    /**
     * @return array
     */
    public function getData(): array
    {
        return [
            'key'        => $this->getKey(),
            'status'     => $this->getStatus(),
            'domain'     => $this->getDomain(),
            'ip'         => $this->getIp(),
            'info'       => [
                'email' => $this->getEmail(),
            ],
            'valid'      => [
                'domain' => $this->isValidDomain(),
                'ip'     => $this->isValidIp(),
            ],
            'connection' => [
                'domain' => $this->getRealDomain(),
                'ip'     => $this->getRealIp(),
            ],
        ];
    }
}
