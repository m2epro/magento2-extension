<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Connection;

/**
 * Class \Ess\M2ePro\Model\Connector\Connection\Request
 */
class Request extends \Ess\M2ePro\Model\AbstractModel
{
    // ########################################

    private $component = null;
    private $componentVersion = null;
    private $command = null;

    private $infoRewrites = [];

    // ########################################

    public function setComponent($value)
    {
        $this->component = (string)$value;
        return $this;
    }

    public function getComponent()
    {
        return $this->component;
    }

    // ----------------------------------------

    public function setComponentVersion($value)
    {
        $this->componentVersion = (int)$value;
        return $this;
    }

    public function getComponentVersion()
    {
        return $this->componentVersion;
    }

    // ----------------------------------------

    public function setCommand(array $value)
    {
        $value = array_values($value);

        if (count($value) != 3) {
            throw new \Exception('Invalid Command Format.');
        }

        $this->command = $value;
        return $this;
    }

    public function getCommand()
    {
        return $this->command;
    }

    // ########################################

    public function getInfo()
    {
        $data = [
            'client' => [
                'platform' => [
                    'name' => $this->getHelper('Magento')->getName().
                                ' ('.$this->getHelper('Magento')->getEditionName().')',
                    'version' => $this->getHelper('Magento')->getVersion(),
                    'revision' => $this->getHelper('Magento')->getRevision(),
                ],
                'module' => [
                    'name' => $this->getHelper('Module')->getName(),
                    'version' => $this->getHelper('Module')->getPublicVersion(),
                    'revision' => $this->getHelper('Module')->getRevision()
                ],
                'location' => [
                    'domain' => $this->getHelper('Client')->getDomain(),
                    'ip' => $this->getHelper('Client')->getIp(),
                    'directory' => $this->getHelper('Client')->getBaseDirectory()
                ],
                'locale' => $this->getHelper('Magento')->getLocaleCode()
            ],
            'auth' => [],
            'component' => [
                'name' => $this->component,
                'version' => $this->componentVersion
            ],
            'command' => [
                'entity' => $this->command[0],
                'type' => $this->command[1],
                'name' => $this->command[2]
            ]
        ];

        $adminKey = $this->getHelper('Server')->getAdminKey();
        $adminKey !== null && $adminKey != '' && $data['auth']['admin_key'] = $adminKey;

        $applicationKey = $this->getHelper('Server')->getApplicationKey();
        $applicationKey !== null && $applicationKey != '' && $data['auth']['application_key'] = $applicationKey;

        $licenseKey = $this->getHelper('Module\License')->getKey();
        $licenseKey !== null && $licenseKey != '' && $data['auth']['license_key'] = $licenseKey;

        $installationKey = $this->getHelper('Module')->getInstallationKey();
        $installationKey !== null && $installationKey != '' && $data['auth']['installation_key'] = $installationKey;

        return array_merge_recursive($data, $this->infoRewrites);
    }

    public function setInfoRewrites(array $value = [])
    {
        $this->infoRewrites = $value;
        return $this;
    }

    // ########################################

    public function getPackage()
    {
        return [
            'info' => $this->getInfo(),
            'data' => $this->getData()
        ];
    }

    // ########################################
}
