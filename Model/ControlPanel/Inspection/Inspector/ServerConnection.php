<?php

namespace Ess\M2ePro\Model\ControlPanel\Inspection\Inspector;

use Ess\M2ePro\Model\ControlPanel\Inspection\AbstractInspection;
use Ess\M2ePro\Model\ControlPanel\Inspection\InspectorInterface;
use Ess\M2ePro\Model\ControlPanel\Inspection\Manager;
use Ess\M2ePro\Model\Exception\Connection;

class ServerConnection extends AbstractInspection implements InspectorInterface
{
    //########################################

    public function getTitle()
    {
        return 'Connection with server';
    }

    public function getGroup()
    {
        return Manager::GROUP_GENERAL;
    }

    public function getExecutionSpeed()
    {
        return Manager::EXECUTION_SPEED_FAST;
    }

    //########################################

    public function process()
    {
        $issues = [];

        try {
            $response = $this->helperFactory->getObject('Server_Request')->single(
                ['timeout' => 30],
                null,
                null,
                false,
                false,
                true
            );

            $decoded = $this->helperFactory->getObject('Data')->jsonDecode($response['body']);
            if (empty($decoded['response']['result'])) {
                $issues[] = $this->resultFactory->createError(
                    $this,
                    'Connection Failed',
                    $response['curl_info']
                );
            }
        } catch (Connection $exception) {
            $additionalData = $exception->getAdditionalData();
            $curlInfo = [];

            if (!empty($additionalData['curl_info'])) {
                $curlInfo = $additionalData['curl_info'];
            }

            $issues[] = $this->resultFactory->createError(
                $this,
                $exception->getMessage(),
                $curlInfo
            );
        }

        return $issues;
    }

    //########################################
}
