<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Module;

class License extends \Ess\M2ePro\Helper\AbstractHelper
{
    protected $modelFactory;
    protected $primaryConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Config\Manager\Primary $primaryConfig,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->modelFactory = $modelFactory;
        $this->primaryConfig = $primaryConfig;
        parent::__construct($helperFactory, $context);
    }

    //########################################

    public function getKey()
    {
        $key = $this->primaryConfig->getGroupValue(
            '/'.$this->getHelper('Module')->getName().'/license/','key'
        );
        return !is_null($key) ? (string)$key : '';
    }

    // ---------------------------------------

    public function getStatus()
    {
        $status = $this->primaryConfig->getGroupValue(
            '/'.$this->getHelper('Module')->getName().'/license/','status'
        );
        return (bool)$status;
    }

    // ---------------------------------------

    public function getDomain()
    {
        $domain = $this->primaryConfig->getGroupValue(
            '/'.$this->getHelper('Module')->getName().'/license/','domain'
        );
        return !is_null($domain) ? (string)$domain : '';
    }

    public function getIp()
    {
        $ip = $this->primaryConfig->getGroupValue(
            '/'.$this->getHelper('Module')->getName().'/license/','ip'
        );
        return !is_null($ip) ? (string)$ip : '';
    }

    // ---------------------------------------

    public function getEmail()
    {
        $email = $this->primaryConfig->getGroupValue(
            '/'.$this->getHelper('Module')->getName().'/license/info/','email'
        );
        return !is_null($email) ? (string)$email : '';
    }

    // ---------------------------------------

    public function isValidDomain()
    {
        $isValid = $this->primaryConfig->getGroupValue(
            '/'.$this->getHelper('Module')->getName().'/license/valid/','domain');
        return is_null($isValid) || (bool)$isValid;
    }

    public function isValidIp()
    {
        $isValid = $this->primaryConfig->getGroupValue(
            '/'.$this->getHelper('Module')->getName().'/license/valid/','ip');
        return is_null($isValid) || (bool)$isValid;
    }

    //########################################

    public function obtainRecord($email = NULL, $firstName = NULL, $lastName = NULL,
                                 $country = NULL, $city = NULL, $postalCode = NULL)
    {
        $requestParams = array(
            'domain' => $this->getHelper('Client')->getDomain(),
            'directory' => $this->getHelper('Client')->getBaseDirectory()
        );

        !is_null($email) && $requestParams['email'] = $email;
        !is_null($firstName) && $requestParams['first_name'] = $firstName;
        !is_null($lastName) && $requestParams['last_name'] = $lastName;
        !is_null($country) && $requestParams['country'] = $country;
        !is_null($city) && $requestParams['city'] = $city;
        !is_null($postalCode) && $requestParams['postal_code'] = $postalCode;

        try {

            $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector('license', 'add', 'record',
                                                                   $requestParams);
            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();

        } catch (\Exception $e) {
            return false;
        }

        if (!isset($response['key'])) {
            return false;
        }

        $this->primaryConfig->setGroupValue(
            '/'.$this->getHelper('Module')->getName().'/license/','key',(string)$response['key']
        );

        $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
            $this->modelFactory->getObject('Servicing\Task\License')->getPublicNick()
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
            $connectorObj = $dispatcherObject->getVirtualConnector('license','set','trial',
                                                                   array('key' => $this->getKey(),
                                                                         'component' => $component));
            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();

        } catch (\Exception $exception) {
            return false;
        }

        if (!isset($response['status']) || !$response['status']) {
            return false;
        }

        $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
            $this->modelFactory->getObject('Servicing\Task\License')->getPublicNick()
        );

        return true;
    }

    //########################################
}