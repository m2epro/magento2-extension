<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector;

interface ProtocolInterface
{
    /**
     * @return string
     */
    public function getComponent(): string;

    /**
     * @return int
     */
    public function getComponentVersion(): int;
}
