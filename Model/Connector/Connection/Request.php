<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Connection;

class Request extends \Ess\M2ePro\Model\AbstractModel
{
    // ########################################

    private $component = NULL;
    private $componentVersion = NULL;
    private $command = NULL;

    private $infoRewrites = array();

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
        $data = array(
            'mode' => $this->getHelper('Module')->isDevelopmentEnvironment() ? 'development' : 'production',
            'client' => array(
                'platform' => array(
                    'name' => $this->getHelper('Magento')->getName().
                                ' ('.$this->getHelper('Magento')->getEditionName().')',
                    'version' => $this->getHelper('Magento')->getVersion(),
                    'revision' => $this->getHelper('Magento')->getRevision(),
                ),
                'module' => array(
                    'name' => $this->getHelper('Module')->getName(),
                    'version' => $this->getHelper('Module')->getPublicVersion(),
                    'revision' => $this->getHelper('Module')->getRevision()
                ),
                'location' => array(
                    'domain' => $this->getHelper('Client')->getDomain(),
                    'ip' => $this->getHelper('Client')->getIp(),
                    'directory' => $this->getHelper('Client')->getBaseDirectory()
                ),
                'locale' => $this->getHelper('Magento')->getLocaleCode()
            ),
            'auth' => array(),
            'component' => array(
                'name' => $this->component,
                'version' => $this->componentVersion
            ),
            'command' => array(
                'entity' => $this->command[0],
                'type' => $this->command[1],
                'name' => $this->command[2]
            )
        );

        $adminKey = $this->getHelper('Server')->getAdminKey();
        !is_null($adminKey) && $adminKey != '' && $data['auth']['admin_key'] = $adminKey;

        $applicationKey = $this->getHelper('Server')->getApplicationKey();
        !is_null($applicationKey) && $applicationKey != '' && $data['auth']['application_key'] = $applicationKey;

        $licenseKey = $this->getHelper('Module\License')->getKey();
        !is_null($licenseKey) && $licenseKey != '' && $data['auth']['license_key'] = $licenseKey;

        $installationKey = $this->getHelper('Module')->getInstallationKey();
        !is_null($installationKey) && $installationKey != '' && $data['auth']['installation_key'] = $installationKey;

        return array_merge_recursive($data,$this->infoRewrites);
    }

    public function setInfoRewrites(array $value = array())
    {
        $this->infoRewrites = $value;
        return $this;
    }

    // ########################################

    public function getPackage()
    {
        return array(
            'info' => $this->getInfo(),
            'data' => $this->getData()
        );
    }

    // ########################################
}