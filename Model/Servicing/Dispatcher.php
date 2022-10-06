<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing;

use Ess\M2ePro\Model\Servicing\Task\ {Messages,
    License,
    Settings,
    Marketplaces,
    Cron,
    Statistic,
    Analytics,
    MaintenanceSchedule,
    ProductVariationVocabulary};

class Dispatcher
{
    private const DEFAULT_INTERVAL = 3600;
    private const MAX_MEMORY_LIMIT = 256;
    private const SERVER_TASKS_CLASS = [
        Messages::NAME                   => Messages::class,
        License::NAME                    => License::class,
        Settings::NAME                   => Settings::class,
        Marketplaces::NAME               => Marketplaces::class,
        Cron::NAME                       => Cron::class,
        Statistic::NAME                  => Statistic::class,
        Analytics::NAME                  => Analytics::class,
        MaintenanceSchedule::NAME        => MaintenanceSchedule::class,
        ProductVariationVocabulary::NAME => ProductVariationVocabulary::class,
    ];

    /** @var \Ess\M2ePro\Helper\Client */
    private $helperClient;
    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $helperException;
    /** @var \Ess\M2ePro\Model\Registry\Manager */
    private $registryManager;
    /** @var \Magento\Framework\ObjectManagerInterface */
    private $objectManager;
    /** @var \Ess\M2ePro\Model\M2ePro\Connector\Dispatcher */
    private $connectorDispatcher;

    /**
     * @param \Ess\M2ePro\Helper\Client $helperClient
     * @param \Ess\M2ePro\Helper\Module\Exception $helperException
     * @param \Ess\M2ePro\Model\Registry\Manager $registryManager
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Ess\M2ePro\Model\M2ePro\Connector\Dispatcher $connectorDispatcher
     */
    public function __construct(
        \Ess\M2ePro\Helper\Client $helperClient,
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Model\Registry\Manager $registryManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Model\M2ePro\Connector\Dispatcher $connectorDispatcher
    ) {
        $this->helperClient = $helperClient;
        $this->helperException = $helperException;
        $this->registryManager = $registryManager;
        $this->objectManager = $objectManager;
        $this->connectorDispatcher = $connectorDispatcher;
    }

    // ---------------------------------------

    /**
     * @param $taskCodes
     *
     * @return void
     * @throws \Exception
     */
    public function process($taskCodes = null): void
    {
        if (!is_array($taskCodes)) {
            $taskCodes = $this->getRegisteredTasks();
        }

        $lastUpdate = $this->getLastUpdateDate();
        $currentDate = \Ess\M2ePro\Helper\Date::createCurrentGmt();

        if (
            $lastUpdate !== null
            && $lastUpdate->getTimestamp() + self::DEFAULT_INTERVAL > $currentDate->getTimestamp()
        ) {
            return;
        }

        $this->setLastUpdateDateTime();
        $this->processTasks($taskCodes);
    }

    /**
     * @param string $taskCode
     *
     * @return void
     */
    public function processTask(string $taskCode): void
    {
        $this->processTasks([$taskCode]);
    }

    /**
     * @param array $taskCodes
     *
     * @return void
     */
    private function processTasks(array $taskCodes): void
    {
        $this->helperClient->setMemoryLimit(self::MAX_MEMORY_LIMIT);
        $this->helperException->setFatalErrorHandler();
        $tasksModel = $this->getTasksModel($taskCodes);

        $connectorObj = $this->connectorDispatcher->getConnector(
            'server',
            'servicing',
            'updateData',
            $this->getRequestData($tasksModel)
        );

        $this->connectorDispatcher->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        if (is_array($responseData)) {
            $this->dispatchResponseData($responseData, $tasksModel);
        }
    }

    // ---------------------------------------

    /**
     * @param array $tasksModel
     *
     * @return array
     */
    private function getRequestData(array $tasksModel): array
    {
        $requestData = [];

        /** @var \Ess\M2ePro\Model\Servicing\TaskInterface $taskModel */

        foreach ($tasksModel as $taskModel) {
            $requestData[$taskModel->getServerTaskName()] = $taskModel->getRequestData();
        }

        return $requestData;
    }

    /**
     * @param array $taskCodes
     *
     * @return array
     */
    private function getTasksModel(array $taskCodes): array
    {
        $tasksModel = [];

        foreach ($this->getRegisteredTasks() as $taskName) {
            if (!in_array($taskName, $taskCodes)) {
                continue;
            }

            $taskModel = $this->getTaskModel($taskName);

            if (!$taskModel->isAllowed()) {
                continue;
            }
            $tasksModel[] = $taskModel;
        }

        return $tasksModel;
    }

    // ---------------------------------------

    /**
     * @param array $responseData
     * @param array $tasksModel
     *
     * @return void
     */
    private function dispatchResponseData(array $responseData, array $tasksModel): void
    {
        /** @var \Ess\M2ePro\Model\Servicing\TaskInterface $taskModel */

        foreach ($tasksModel as $taskModel) {
            if (
                !isset($responseData[$taskModel->getServerTaskName()])
                || !is_array($responseData[$taskModel->getServerTaskName()])
            ) {
                continue;
            }

            $taskModel->processResponseData($responseData[$taskModel->getServerTaskName()]);
        }
    }

    // ---------------------------------------

    /**
     * @param string $taskName
     *
     * @return \Ess\M2ePro\Model\Servicing\TaskInterface
     */
    private function getTaskModel(string $taskName): TaskInterface
    {
        $taskName = $this->getTaskClass($taskName);

        /** @var \Ess\M2ePro\Model\Servicing\TaskInterface $taskModel */
        $taskModel = $this->objectManager->create($taskName);

        return $taskModel;
    }

    // ---------------------------------------

    /**
     * @return array
     */
    public function getRegisteredTasks(): array
    {
        return array_keys(self::SERVER_TASKS_CLASS);
    }

    /**
     * @param string $taskName
     *
     * @return string
     */
    private function getTaskClass(string $taskName): string
    {
        return self::SERVER_TASKS_CLASS[$taskName];
    }

    /**
     * @return array
     */
    public function getSlowTasks(): array
    {
        return [
            'statistic',
            'analytics',
        ];
    }

    /**
     * @return array
     */
    public function getFastTasks(): array
    {
        return array_diff($this->getRegisteredTasks(), $this->getSlowTasks());
    }

    // ---------------------------------------

    /**
     * @return \DateTime|null
     * @throws \Exception
     */
    private function getLastUpdateDate(): ?\DateTime
    {
        $lastUpdateDate = $this->registryManager->getValue('/servicing/last_update_time/');

        if ($lastUpdateDate !== null) {
            $lastUpdateDate = \Ess\M2ePro\Helper\Date::createDateGmt($lastUpdateDate);
        }

        return $lastUpdateDate;
    }

    /**
     * @return void
     * @throws \Exception
     */
    private function setLastUpdateDateTime(): void
    {
        $this->registryManager->setValue(
            '/servicing/last_update_time/',
            \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s')
        );
    }
}
