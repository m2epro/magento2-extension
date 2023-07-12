<?php

namespace Ess\M2ePro\Helper\Component\Ebay;

class BlockingErrorConfig
{
    private const CONFIG_GROUP = '/blocking_errors/ebay/';

    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $configManager;

    public function __construct(\Ess\M2ePro\Model\Config\Manager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function getEbayBlockingErrorRetrySeconds(): int
    {
        return (int)$this->configManager
            ->getGroupValue(self::CONFIG_GROUP, 'retry_seconds');
    }

    /**
     * @return string[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getEbayBlockingErrorsList(): array
    {
        $value = $this->configManager
            ->getGroupValue(self::CONFIG_GROUP, 'errors_list');

        if ($value === null) {
            return [];
        }

        return \Ess\M2ePro\Helper\Json::decode($value);
    }
}
