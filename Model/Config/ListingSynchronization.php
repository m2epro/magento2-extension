<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Config;

class ListingSynchronization
{
    private const CONFIG_KEY_PROCESS_INSTRUCTION_MODE_PATTERN = '/cron/task/%s/listing/product/process_instructions/';

    private Manager $config;

    public function __construct(Manager $config)
    {
        $this->config = $config;
    }

    public function getComponentMode(string $component): int
    {
        $result = $this->config->getGroupValue(
            $this->getGroupPath($component),
            'mode'
        );

        return $result ? (int)$result : 0;
    }

    public function isEnabled(string $component): bool
    {
        $result = $this->config->getGroupValue(
            $this->getGroupPath($component),
            'mode'
        );

        return (bool)$result;
    }

    public function setAmazonMode(int $mode): bool
    {
        return $this->setMode(\Ess\M2ePro\Helper\Component\Amazon::NICK, $mode);
    }

    public function setEbayMode(int $mode): bool
    {
        return $this->setMode(\Ess\M2ePro\Helper\Component\Ebay::NICK, $mode);
    }

    public function setWalmartMode(int $mode): bool
    {
        return $this->setMode(\Ess\M2ePro\Helper\Component\Walmart::NICK, $mode);
    }

    private function setMode(string $component, int $mode): bool
    {
        return $this->config->setGroupValue(
            $this->getGroupPath($component),
            'mode',
            $mode
        );
    }

    private function getGroupPath(string $component): string
    {
        return sprintf(self::CONFIG_KEY_PROCESS_INSTRUCTION_MODE_PATTERN, $component);
    }
}
