<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Order\Cancellation;

class ByBuyer extends \Ess\M2ePro\Model\Ebay\Connector\Command\RealTime
{
    protected function getCommand()
    {
        return ['orders', 'cancel', 'byBuyer'];
    }

    protected function getRequestData()
    {
        return [
            'action' => $this->params['action'],
            'order_id' => $this->params['order_id'],
        ];
    }

    protected function validateResponse()
    {
        $response = $this->getResponse()->getResponseData();

        return isset($response['result']) && is_bool($response['result']);
    }

    protected function prepareResponseData()
    {
        $responseData = $this->getResponse()->getResponseData();

        $this->responseData = [
            'result' => $responseData['result'],
        ];
    }
}
