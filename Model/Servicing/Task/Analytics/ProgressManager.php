<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task\Analytics;

class ProgressManager
{
    /** @var \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry */
    private $registry;
    /** @var string */
    private $collectorId;

    public function __construct(string $collectorId, Registry $registry)
    {
        $this->registry = $registry;
        $this->collectorId = $collectorId;
    }

    public function isInProcess(): bool
    {
        return $this->getLastId() !== null;
    }

    public function isCompleted(): bool
    {
        return $this->getCurrent() >= $this->getLastId();
    }

    public function start(int $lastId): void
    {
        $this->setLastId($lastId);
        $this->setCurrent(0);
    }

    public function setCurrent(int $id): void
    {
        $this->registry->setProgressData($this->collectorId, 'last_processed_id', $id);
    }

    public function getCurrent(): ?int
    {
        return (int)$this->registry->getProgressData($this->collectorId, 'last_processed_id');
    }

    private function setLastId(int $id): void
    {
        $this->registry->setProgressData($this->collectorId, 'last_id', $id);
    }

    public function getLastId(): ?int
    {
        return $this->registry->getProgressData($this->collectorId, 'last_id');
    }
}
