<?php

namespace Ess\M2ePro\Model\M2ePro\Connector;

class Protocol implements \Ess\M2ePro\Model\Connector\ProtocolInterface
{
    public const COMPONENT_VERSION = 9;

    public function getComponent(): string
    {
        return 'M2ePro';
    }

    public function getComponentVersion(): int
    {
        return self::COMPONENT_VERSION;
    }
}
