<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

class MaintenanceSchedule implements \Ess\M2ePro\Model\Servicing\TaskInterface
{
    public const NAME = 'maintenance_schedule';

    /** @var \Ess\M2ePro\Helper\Server\Maintenance */
    private $serverMaintenance;

    /**
     * @param \Ess\M2ePro\Helper\Server\Maintenance $serverMaintenance
     */
    public function __construct(\Ess\M2ePro\Helper\Server\Maintenance $serverMaintenance)
    {
        $this->serverMaintenance = $serverMaintenance;
    }

    /**
     * @return string
     */
    public function getServerTaskName(): string
    {
        return self::NAME;
    }

    // ----------------------------------------

    /**
     * @return bool
     */
    public function isAllowed(): bool
    {
        return true;
    }

    // ----------------------------------------

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return [];
    }

    /**
     * @param array $data
     *
     * @throws \Exception
     */
    public function processResponseData(array $data): void
    {
        $dateEnabledFrom = false;
        $dateEnabledTo = false;

        if (!empty($data['date_enabled_from']) && !empty($data['date_enabled_to'])) {
            $dateEnabledFrom = $data['date_enabled_from'];
            $dateEnabledTo = $data['date_enabled_to'];
        }

        if ($this->serverMaintenance->getDateEnabledFrom() != $dateEnabledFrom) {
            $this->serverMaintenance->setDateEnabledFrom($dateEnabledFrom);
        }

        if ($this->serverMaintenance->getDateEnabledTo() != $dateEnabledTo) {
            $this->serverMaintenance->setDateEnabledTo($dateEnabledTo);
        }
    }
}
