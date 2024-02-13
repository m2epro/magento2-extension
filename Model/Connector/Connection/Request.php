<?php

namespace Ess\M2ePro\Model\Connector\Connection;

class Request extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;
    /** @var \Ess\M2ePro\Helper\Module */
    private $moduleHelper;
    /** @var \Ess\M2ePro\Helper\Module\License */
    private $moduleLicenseHelper;
    /** @var \Ess\M2ePro\Helper\Client */
    private $clientHelper;
    /** @var \Ess\M2ePro\Helper\Server */
    private $serverHelper;
    /** @var string  */
    private $component;
    /** @var int  */
    private $componentVersion;
    /** @var array */
    private $command;
    /** @var string|null  */
    private $rawData = null;

    public function __construct(
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Module\License $moduleLicenseHelper,
        \Ess\M2ePro\Helper\Client $clientHelper,
        \Ess\M2ePro\Helper\Server $serverHelper,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->magentoHelper = $magentoHelper;
        $this->moduleHelper = $moduleHelper;
        $this->moduleLicenseHelper = $moduleLicenseHelper;
        $this->clientHelper = $clientHelper;
        $this->serverHelper = $serverHelper;
    }

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
            throw new \Ess\M2ePro\Model\Exception('Invalid Command Format.');
        }

        $this->command = $value;

        return $this;
    }

    public function getCommand()
    {
        return $this->command;
    }

    // ----------------------------------------

    public function getInfo(): array
    {
        $data = [
            'client' => [
                'platform' => [
                    'name' => $this->magentoHelper->getName() .
                        ' (' . $this->magentoHelper->getEditionName() . ')',
                    'version' => $this->magentoHelper->getVersion(),
                ],
                'module' => [
                    'name' => $this->moduleHelper->getName(),
                    'version' => $this->moduleHelper->getPublicVersion(),
                ],
                'location' => [
                    'domain' => $this->clientHelper->getDomain(),
                    'ip' => $this->clientHelper->getIp(),
                ],
            ],
            'auth' => [
                'application_key' => $this->serverHelper->getApplicationKey(),
            ],
            'component' => [
                'name' => $this->component,
                'version' => $this->componentVersion,
            ],
            'command' => [
                'entity' => $this->command[0],
                'type' => $this->command[1],
                'name' => $this->command[2],
            ],
        ];

        $licenseKey = $this->moduleLicenseHelper->getKey();
        if (!empty($licenseKey)) {
            $data['auth']['license_key'] = $licenseKey;
        }

        return $data;
    }

    // ---------------------------------------

    public function setRawData($value)
    {
        $this->rawData = $value;

        return $this;
    }

    public function getRawData()
    {
        return $this->rawData;
    }

    // ----------------------------------------

    public function getPackage()
    {
        return [
            'info' => $this->getInfo(),
            'data' => $this->getData(),
        ];
    }

    // ----------------------------------------
}
