<?php

namespace Ess\M2ePro\Model\Amazon\Connector;

class Protocol implements \Ess\M2ePro\Model\Connector\ProtocolInterface
{
    public const COMPONENT_VERSION = 23;

    public function getComponent(): string
    {
        return 'Amazon';
    }

    public function getComponentVersion(): int
    {
        return self::COMPONENT_VERSION;
    }
}
