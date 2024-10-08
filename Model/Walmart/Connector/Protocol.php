<?php

namespace Ess\M2ePro\Model\Walmart\Connector;

class Protocol implements \Ess\M2ePro\Model\Connector\ProtocolInterface
{
    public const COMPONENT_VERSION = 6;

    public function getComponent(): string
    {
        return 'Walmart';
    }

    public function getComponentVersion(): int
    {
        return self::COMPONENT_VERSION;
    }
}
