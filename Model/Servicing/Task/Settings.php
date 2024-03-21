<?php

namespace Ess\M2ePro\Model\Servicing\Task;

class Settings implements \Ess\M2ePro\Model\Servicing\TaskInterface
{
    public const NAME = 'settings';

    /** @var \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry */
    private $registry;
    /** @var \Ess\M2ePro\Model\Servicing\Task\Statistic\Manager */
    private $statisticManager;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registryManager;

    /**
     * @param \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry $registry
     * @param \Ess\M2ePro\Model\Servicing\Task\Statistic\Manager $statisticManager
     * @param \Ess\M2ePro\Model\Registry\Manager $registryManager
     */
    public function __construct(
        \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry $registry,
        \Ess\M2ePro\Model\Servicing\Task\Statistic\Manager $statisticManager,
        \Ess\M2ePro\Model\Registry\Manager $registryManager
    ) {
        $this->registry = $registry;
        $this->statisticManager = $statisticManager;
        $this->registryManager = $registryManager;
    }

    // ----------------------------------------

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

    // ----------------------------------------

    /**
     * @param array $data
     *
     * @return void
     */
    public function processResponseData(array $data): void
    {
        $this->updateLastVersion($data);
        $this->updateAnalytics($data);
        $this->updateStatistic($data);
    }

    // ----------------------------------------

    /**
     * @param array $data
     *
     * @return void
     */
    private function updateLastVersion(array $data): void
    {
        if (empty($data['last_version'])) {
            return;
        }

        $this->registryManager->setValue(
            '/installation/public_last_version/',
            $data['last_version']['magento_2']['public']
        );
    }

    /**
     * @param array $data
     *
     * @return void
     */
    private function updateAnalytics(array $data): void
    {
        if (empty($data['analytics'])) {
            return;
        }

        if (
            isset($data['analytics']['planned_at']) &&
            $data['analytics']['planned_at'] !== $this->registry->getPlannedAt()
        ) {
            $this->registry->markPlannedAt($data['analytics']['planned_at']);
        }
    }

    /**
     * @param array $data
     *
     * @return void
     */
    private function updateStatistic(array $data): void
    {
        // A list of tasks to be enabled/disabled from the server
        $tasks = [
            \Ess\M2ePro\Model\Servicing\Task\Statistic\Manager::TASK_LISTING_PRODUCT_INSTRUCTION_TYPE => false,
        ];

        if (isset($data['statistic']['tasks'])) {
            foreach ($data['statistic']['tasks'] as $key => $value) {
                if (isset($tasks[$key])) {
                    $tasks[$key] = (bool)$value;
                }
            }
        }

        $this->statisticManager->setTasksStates($tasks);
    }
}
