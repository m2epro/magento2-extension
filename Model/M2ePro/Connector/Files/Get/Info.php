<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\M2ePro\Connector\Files\Get;

class Info extends \Ess\M2ePro\Model\Connector\Command\RealTime
{
    protected function getCommand(): array
    {
        return ['files', 'get', 'info'];
    }

    protected function getRequestData(): array
    {
        return [];
    }

    protected function prepareResponseData()
    {
        $response = $this->getResponse()->getResponseData();

        if (!isset($response['files_info'])) {
            $this->responseData = [];

            return;
        }

        $preparedData = [];

        foreach ($response['files_info'] as $info) {
            $preparedData[$info['path']] = $info['hash'];
        }

        $this->responseData = $preparedData;
    }
}
