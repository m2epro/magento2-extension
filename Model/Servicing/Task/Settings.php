<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

class Settings extends \Ess\M2ePro\Model\Servicing\Task
{
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
        $requestData = array();

        $tempValue = $this->cacheConfig->getGroupValue('/server/location/','default_index_given_by_server_at');
        if ($tempValue) {

            $primaryConfig = $this->getHelper('Primary')->getConfig();
            $requestData['current_default_server_baseurl_index'] = $primaryConfig->getGroupValue(
                '/server/location/', 'default_index'
            );
        }

        return $requestData;
    }

    public function processResponseData(array $data)
    {
        $this->updateServersBaseUrls($data);
        $this->updateDefaultServerBaseUrlIndex($data);
        $this->updateCronHosts($data);
        $this->updateLastVersion($data);
        $this->updateSendLogs($data);
    }

    //########################################

    private function updateServersBaseUrls(array $data)
    {
        if (!is_array($data['servers_baseurls']) || empty($data['servers_baseurls'])) {
            return;
        }

        $index = 1;
        $configUpdates = array();

        $config = $this->getHelper('Primary')->getConfig();

        foreach ($data['servers_baseurls'] as $newHostName => $newBaseUrl) {

            $oldHostName = $config->getGroupValue('/server/location/'.$index.'/','hostname');
            $oldBaseUrl  = $config->getGroupValue('/server/location/'.$index.'/','baseurl');

            if ($oldHostName != $newHostName || $oldBaseUrl != $newBaseUrl) {
                $configUpdates[$index] = array(
                    'hostname' => $newHostName,
                    'baseurl' => $newBaseUrl
                );
            }

            $index++;
        }

        for ($deletedIndex = $index; $deletedIndex < 100; $deletedIndex++) {

            $deletedHostName = $config->getGroupValue('/server/location/'.$deletedIndex.'/','hostname');
            $deletedBaseUrl  = $config->getGroupValue('/server/location/'.$deletedIndex.'/','baseurl');

            if (is_null($deletedHostName) && is_null($deletedBaseUrl)) {
                break;
            }

            $config->deleteGroupValue('/server/location/'.$deletedIndex.'/','hostname');
            $config->deleteGroupValue('/server/location/'.$deletedIndex.'/','baseurl');
        }

        if (empty($configUpdates)) {
            return;
        }

        try {

            foreach ($configUpdates as $index => $change) {

                $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');

                $connectorObj = $dispatcherObject->getConnector('server','check','state',
                                                                array(
                                                                    'base_url' => $change['baseurl'].'index.php',
                                                                    'hostname' => $change['hostname'],
                                                                ));
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

        $this->getHelper('Primary')->getConfig()->setGroupValue(
            '/server/location/','default_index',(int)$data['default_server_baseurl_index']
        );

        $this->cacheConfig->setGroupValue(
            '/server/location/', 'default_index_given_by_server_at', $this->getHelper('Data')->getCurrentGmtDate()
        );
    }

    private function updateCronHosts(array $data)
    {
        if (!isset($data['cron_domains'])) {
            return;
        }

        $index = 1;
        $config = $this->getHelper('Module')->getConfig();

        foreach ($data['cron_domains'] as $newCronHost) {

            $oldGroupValue = $config->getGroupValue('/cron/service/','hostname_'.$index);

            if ($oldGroupValue != $newCronHost) {
                $config->setGroupValue('/cron/service/','hostname_'.$index, $newCronHost);
            }

            $index++;
        }

        for ($i = $index; $i < 100; $i++) {

            $oldGroupValue = $config->getGroupValue('/cron/service/','hostname_'.$i);

            if (is_null($oldGroupValue)) {
                break;
            }

            $config->deleteGroupValue('/server/','hostname_'.$i);
        }
    }

    private function updateLastVersion(array $data)
    {
        if (empty($data['last_version'])) {
            return;
        }

        $this->cacheConfig->setGroupValue(
            '/installation/', 'public_last_version', $data['last_version']['magento_2']['public']
        );
        $this->cacheConfig->setGroupValue(
            '/installation/', 'build_last_version', $data['last_version']['magento_2']['build']
        );
    }

    private function updateSendLogs(array $data)
    {
        if (!isset($data['send_logs'])) {
            return;
        }

        $this->getHelper('Module')->getConfig()->setGroupValue(
            '/debug/logging/', 'send_to_server', (int)$data['send_logs']
        );
    }

    //########################################
}