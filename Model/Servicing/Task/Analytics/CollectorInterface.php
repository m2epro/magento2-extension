<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics;

interface CollectorInterface
{
    public const LIMIT = 500;
    public function getComponent(): string;
    public function getEntityName(): string;
    public function getLastEntityId(): int;
    /**
     * @return \Ess\M2ePro\Model\Servicing\Task\Analytics\Row[]
     */
    public function getRows(int $fromId, int $toId): iterable;
}
