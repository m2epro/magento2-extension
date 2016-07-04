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

        $tempValue = $this->cacheConfig->getGroupValue('/default_baseurl_index/',
                                                                                    'given_by_server_at');
        if ($tempValue) {

            $primaryConfig = $this->getHelper('Primary')->getConfig();
            $requestData['current_default_server_baseurl_index'] = $primaryConfig->getGroupValue(
                '/server/', 'default_baseurl_index'
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

            $oldHostName = $config->getGroupValue('/server/','hostname_'.$index);
            $oldBaseUrl  = $config->getGroupValue('/server/','baseurl_'.$index);

            if ($oldHostName != $newHostName || $oldBaseUrl != $newBaseUrl) {
                $configUpdates[$index] = array(
                    'hostname' => $newHostName,
                    'baseurl' => $newBaseUrl
                );
            }

            $index++;
        }

        for ($deletedIndex = $index; $deletedIndex < 100; $deletedIndex++) {

            $deletedHostName = $config->getGroupValue('/server/','hostname_'.$deletedIndex);
            $deletedBaseUrl  = $config->getGroupValue('/server/','baseurl_'.$deletedIndex);

            if (is_null($deletedHostName) && is_null($deletedBaseUrl)) {
                break;
            }

            $config->deleteGroupValue('/server/','hostname_'.$deletedIndex);
            $config->deleteGroupValue('/server/','baseurl_'.$deletedIndex);
        }

        if (empty($configUpdates)) {
            return;
        }

        try {

            foreach ($configUpdates as $index => $change) {

                $dispatcherObject = $this->modelFactory->getObject('M2ePro\Connector\Dispatcher');

                $connectorObj = $dispatcherObject->getVirtualConnector('server','check','state',
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
            $config->setGroupValue('/server/', 'hostname_'.$index, $change['hostname']);
            $config->setGroupValue('/server/', 'baseurl_'.$index, $change['baseurl']);
        }
    }

    private function updateDefaultServerBaseUrlIndex(array $data)
    {
        if (!isset($data['default_server_baseurl_index']) || (int)$data['default_server_baseurl_index'] <= 0) {
            return;
        }

        $this->getHelper('Primary')->getConfig()->setGroupValue(
            '/server/','default_baseurl_index',(int)$data['default_server_baseurl_index']
        );

        $this->cacheConfig->setGroupValue(
            '/default_baseurl_index/', 'given_by_server_at', $this->getHelper('Data')->getCurrentGmtDate()
        );
    }

    private function updateLastVersion(array $data)
    {
        if (empty($data['last_version'])) {
            return;
        }

        $this->cacheConfig->setGroupValue(
            '/installation/', 'last_version', $data['last_version']
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