<?php

namespace Ess\M2ePro\Helper;

class Server
{
    /** @var \Ess\M2ePro\Model\Config\Manager $config */
    private $config;

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager $config
    ) {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return rtrim((string)$this->config->getGroupValue('/server/', 'host'), '/');
    }

    /**
     * @return string
     */
    public function getApplicationKey(): string
    {
        return (string)$this->config->getGroupValue('/server/', 'application_key');
    }
}
