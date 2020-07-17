<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

/**
 * Class \Ess\M2ePro\Model\Servicing\Task\Settings
 */
class Settings extends \Ess\M2ePro\Model\Servicing\Task
{
    protected $registry;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Servicing\Task\Analytics\Registry $registry,
        \Magento\Eav\Model\Config $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
    ) {
        $this->registry = $registry;

        parent::__construct(
            $config,
            $storeManager,
            $modelFactory,
            $helperFactory,
            $resource,
            $activeRecordFactory,
            $parentFactory
        );
    }

    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'settings';
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        $requestData = [];

        $tempValue = $this->getHelper('Module')->getRegistry()->getValue(
            '/server/location/default_index_given_by_server_at/'
        );
        if ($tempValue) {
            $config = $this->getHelper('Module')->getConfig();
            $requestData['current_default_server_baseurl_index'] = $config->getGroupValue(
                '/server/location/',
                'default_index'
            );
        }

        return $requestData;
    }

    public function processResponseData(array $data)
    {
        $this->updateServersBaseUrls($data);
        $this->updateDefaultServerBaseUrlIndex($data);
        $this->updateLastVersion($data);
        $this->updateSendLogs($data);
        $this->updateAnalytics($data);
    }

    //########################################

    private function updateServersBaseUrls(array $data)
    {
        if (!is_array($data['servers_baseurls']) || empty($data['servers_baseurls'])) {
            return;
        }

        $index = 1;
        $configUpdates = [];

        $config = $this->getHelper('Module')->getConfig();

        foreach ($data['servers_baseurls'] as $newHostName => $newBaseUrl) {
            $oldHostName = $config->getGroupValue('/server/location/'.$index.'/', 'hostname');
            $oldBaseUrl  = $config->getGroupValue('/server/location/'.$index.'/', 'baseurl');

            if ($oldHostName != $newHostName || $oldBaseUrl != $newBaseUrl) {
                $configUpdates[$index] = [
                    'hostname' => $newHostName,
                    'baseurl' => $newBaseUrl
                ];
            }

            $index++;
        }

        for ($deletedIndex = $index; $deletedIndex < 100; $deletedIndex++) {
            $deletedHostName = $config->getGroupValue('/server/location/'.$deletedIndex.'/', 'hostname');
            $deletedBaseUrl  = $config->getGroupValue('/server/location/'.$deletedIndex.'/', 'baseurl');

            if ($deletedHostName === null && $deletedBaseUrl === null) {
                break;
            }

            $config->deleteGroupValue('/server/location/'.$deletedIndex.'/', 'hostname');
            $config->deleteGroupValue('/server/location/'.$deletedIndex.'/', 'baseurl');
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
                        'base_url' => $change['baseurl'].'index.php',
                        'hostname' => $change['hostname'],
                    ]
                );
                $dispatcherObject->process($connectorObj);
                $response = $connectorObj->getResponseData();

                if (empty($response['state'])) {
                    return;
                }
            }
        } catch (\Exception $e) {
            return;
        }

        foreach ($configUpdates as $index => $change) {
            $config->setGroupValue('/server/location/'.$index.'/', 'hostname', $change['hostname']);
            $config->setGroupValue('/server/location/'.$index.'/', 'baseurl', $change['baseurl']);
        }
    }

    private function updateDefaultServerBaseUrlIndex(array $data)
    {
        if (!isset($data['default_server_baseurl_index']) || (int)$data['default_server_baseurl_index'] <= 0) {
            return;
        }

        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/server/location/',
            'default_index',
            (int)$data['default_server_baseurl_index']
        );

        $this->getHelper('Module')->getRegistry()->setValue(
            '/server/location/default_index_given_by_server_at/',
            $this->getHelper('Data')->getCurrentGmtDate()
        );
    }

    private function updateLastVersion(array $data)
    {
        if (empty($data['last_version'])) {
            return;
        }

        $this->getHelper('Module')->getRegistry()->setValue(
            '/installation/public_last_version/',
            $data['last_version']['magento_2']['public']
        );
        $this->getHelper('Module')->getRegistry()->setValue(
            '/installation/build_last_version/',
            $data['last_version']['magento_2']['build']
        );
    }

    private function updateSendLogs(array $data)
    {
        if (!isset($data['send_logs'])) {
            return;
        }

        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/server/logging/',
            'send',
            (int)$data['send_logs']
        );
    }

    private function updateAnalytics(array $data)
    {
        if (empty($data['analytics'])) {
            return;
        }

        if (isset($data['analytics']['planned_at']) &&
            $data['analytics']['planned_at'] !== $this->registry->getPlannedAt()) {
            $this->registry->markPlannedAt($data['analytics']['planned_at']);
        }
    }

    //########################################
}
