<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\M2ePro\Connector;

class Protocol implements \Ess\M2ePro\Model\Connector\ProtocolInterface
{
    public const COMPONENT_VERSION = 8;

    /**
     * @return string
     */
    public function getComponent(): string
    {
        return 'M2ePro';
    }

    /**
     * @return int
     */
    public function getComponentVersion(): int
    {
        return self::COMPONENT_VERSION;
    }
}
