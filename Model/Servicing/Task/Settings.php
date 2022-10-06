<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

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
    /** @var \Ess\M2ePro\Model\Config\Manager */
    private $configManager;

    /**
     * @param \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry $registry
     * @param \Ess\M2ePro\Model\Servicing\Task\Statistic\Manager $statisticManager
     * @param \Ess\M2ePro\Model\Registry\Manager $registryManager
     * @param \Ess\M2ePro\Model\Config\Manager $configManager
     */
    public function __construct(
        \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry $registry,
        \Ess\M2ePro\Model\Servicing\Task\Statistic\Manager $statisticManager,
        \Ess\M2ePro\Model\Registry\Manager $registryManager,
        \Ess\M2ePro\Model\Config\Manager $configManager
    ) {
        $this->registry = $registry;
        $this->statisticManager = $statisticManager;
        $this->registryManager = $registryManager;
        $this->configManager = $configManager;
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
    function isAllowed(): bool
    {
        return true;
    }

    // ----------------------------------------

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        $requestData = [];

        $tempValue = $this->registryManager->getValue(
            '/server/location/default_index_given_by_server_at/'
        );
        if ($tempValue) {
            $requestData['current_default_server_baseurl_index'] = $this->configManager->getGroupValue(
                '/server/location/',
                'default_index'
            );
        }

        return $requestData;
    }

    // ----------------------------------------

    /**
     * @param array $data
     *
     * @return void
     */
    public function processResponseData(array $data): void
    {
        $this->updateServersBaseUrls($data);
        $this->updateDefaultServerBaseUrlIndex($data);
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
    private function updateServersBaseUrls(array $data): void
    {
        if (!is_array($data['servers_baseurls']) || empty($data['servers_baseurls'])) {
            return;
        }

        $index = 1;
        $configUpdates = [];

        foreach ($data['servers_baseurls'] as $newHostName => $newBaseUrl) {
            $oldHostName = $this->configManager->getGroupValue('/server/location/' . $index . '/', 'hostname');
            $oldBaseUrl = $this->configManager->getGroupValue('/server/location/' . $index . '/', 'baseurl');

            if ($oldHostName != $newHostName || $oldBaseUrl != $newBaseUrl) {
                $configUpdates[$index] = [
                    'hostname' => $newHostName,
                    'baseurl'  => $newBaseUrl,
                ];
            }

            $index++;
        }

        for ($deletedIndex = $index; $deletedIndex < 100; $deletedIndex++) {
            $deletedHostName = $this->configManager->getGroupValue(
                '/server/location/' . $deletedIndex . '/',
                'hostname'
            );
            $deletedBaseUrl = $this->configManager->getGroupValue('/server/location/' . $deletedIndex . '/', 'baseurl');

            if ($deletedHostName === null && $deletedBaseUrl === null) {
                break;
            }

            $this->configManager->deleteGroupValue('/server/location/' . $deletedIndex . '/', 'hostname');
            $this->configManager->deleteGroupValue('/server/location/' . $deletedIndex . '/', 'baseurl');
        }

        if (empty($configUpdates)) {
            return;
        }

        try {
            foreach ($configUpdates as $index => $change) {
                $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');

                $connectorObj = $dispatcherObject->getConnector(
                    'server',
                    'check',
                    'state',
                    [
                        'base_url' => $this->cleaningBaseUrl($change['baseurl']) . '/index.php',
                        'hostname' => $change['hostname'],
                    ]
                );
                $dispatcherObject->process($connectorObj);
                $response = $connectorObj->getResponseData();

                if (empty($response['state'])) {
                    return;
                }
            }
        } catch (\Throwable $e) {
            return;
        }

        foreach ($configUpdates as $index => $change) {
            $this->configManager->setGroupValue('/server/location/' . $index . '/', 'hostname', $change['hostname']);
            $this->configManager->setGroupValue('/server/location/' . $index . '/', 'baseurl', $change['baseurl']);
        }
    }

    /**
     * @param array $data
     *
     * @return void
     * @throws \Exception
     */
    private function updateDefaultServerBaseUrlIndex(array $data): void
    {
        if (!isset($data['default_server_baseurl_index']) || (int)$data['default_server_baseurl_index'] <= 0) {
            return;
        }

        $this->configManager->setGroupValue(
            '/server/location/',
            'default_index',
            (int)$data['default_server_baseurl_index']
        );

        $this->registryManager->setValue(
            '/server/location/default_index_given_by_server_at/',
            \Ess\M2ePro\Helper\Date::createCurrentGmt()->format('Y-m-d H:i:s')
        );
    }

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
        $this->registryManager->setValue(
            '/installation/build_last_version/',
            $data['last_version']['magento_2']['build']
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

    // ----------------------------------------

    /**
     * @param string $baseUrl
     *
     * @return string
     */
    private function cleaningBaseUrl(string $baseUrl): string
    {
        $baseUrl = str_replace('index.php', '', $baseUrl);

        return rtrim($baseUrl, '/');
    }

}
