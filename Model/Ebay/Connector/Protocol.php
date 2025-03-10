<?php

namespace Ess\M2ePro\Model\Ebay\Connector;

class Protocol implements \Ess\M2ePro\Model\Connector\ProtocolInterface
{
    public const COMPONENT_VERSION = 28;

    public function getComponent(): string
    {
        return 'Ebay';
    }

    public function getComponentVersion(): int
    {
        return self::COMPONENT_VERSION;
    }
}
