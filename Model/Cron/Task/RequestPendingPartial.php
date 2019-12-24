<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Request\Pending\Partial;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\RequestPendingPartial
 */
class RequestPendingPartial extends AbstractModel
{
    const NICK = 'request_pending_partial';
    const MAX_MEMORY_LIMIT = 512;

    const STATUS_NOT_FOUND  = 'not_found';
    const STATUS_COMPLETE   = 'completed';
    const STATUS_PROCESSING = 'processing';

    const MAX_PARTS_PER_ONE_ITERATION = 3;

    //####################################

    protected function getNick()
    {
        return self::NICK;
    }

    protected function getMaxMemoryLimit()
    {
        return self::MAX_MEMORY_LIMIT;
    }

    //####################################

    protected function performActions()
    {
        $this->removeOutdated();
        $this->completeExpired();
        $this->executeInProgress();
    }

    //####################################

    private function removeOutdated()
    {
        $requestPendingPartialCollection = $this->activeRecordFactory->getObject('Request_Pending_Partial')
            ->getCollection();
        $requestPendingPartialCollection->setOnlyOutdatedItemsFilter();
        $requestPendingPartialCollection->addFieldToFilter('is_completed', 1);

        /** @var Partial[] $requestsPendingPartialObjects */
        $requestsPendingPartialObjects = $requestPendingPartialCollection->getItems();

        foreach ($requestsPendingPartialObjects as $requestsPendingPartialObject) {
            $requestsPendingPartialObject->delete();
        }
    }

    private function completeExpired()
    {
        $requestPendingPartialCollection = $this->activeRecordFactory
            ->getObject('Request_Pending_Partial')->getCollection();
        $requestPendingPartialCollection->setOnlyExpiredItemsFilter();
        $requestPendingPartialCollection->addFieldToFilter('is_completed', 0);

        /** @var \Ess\M2ePro\Model\Request\Pending\Partial[] $expiredRequestPendingPartialObjects */
        $expiredRequestPendingPartialObjects = $requestPendingPartialCollection->getItems();

        foreach ($expiredRequestPendingPartialObjects as $requestPendingPartialObject) {
            $this->completeRequest($requestPendingPartialObject, [$this->getFailedMessage()->asArray()]);
        }
    }

    private function executeInProgress()
    {
        $requestPendingPartialCollection = $this->activeRecordFactory
            ->getObject('Request_Pending_Partial')->getCollection();
        $requestPendingPartialCollection->addFieldToFilter('is_completed', 0);

        /** @var \Ess\M2ePro\Model\Request\Pending\Partial[] $requestPendingPartialObjects */
        $requestPendingPartialObjects = $requestPendingPartialCollection->getItems();

        foreach ($requestPendingPartialObjects as $requestPendingPartial) {
            $this->processRequest($requestPendingPartial);
        }
    }

    //####################################

    private function processRequest(\Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial)
    {
        for ($requestCount = 1; $requestCount <= self::MAX_PARTS_PER_ONE_ITERATION; $requestCount++) {
            $serverData = $this->getServerData($requestPendingPartial);

            if ($serverData['status'] == self::STATUS_NOT_FOUND) {
                $this->completeRequest($requestPendingPartial, [$this->getFailedMessage()->asArray()]);
                break;
            }

            if ($serverData['status'] != self::STATUS_COMPLETE) {
                break;
            }

            $requestPendingPartial->addResultData($serverData['data']);

            if (!empty($serverData['next_part'])) {
                continue;
            }

            $this->completeRequest($requestPendingPartial, $serverData['messages']);
            break;
        }
    }

    private function getServerData(\Ess\M2ePro\Model\Request\Pending\Partial $requestPendingPartial)
    {
        $dispatcher = $this->modelFactory
            ->getObject(ucfirst($requestPendingPartial->getComponent()).'\Connector\Dispatcher');
        $connector = $dispatcher->getVirtualConnector(
            'processing',
            'get',
            'results',
            [
                'processing_id' => $requestPendingPartial->getServerHash(),
                'necessary_parts' => [
                    $requestPendingPartial->getServerHash() => $requestPendingPartial->getNextPart(),
                ],
            ],
            'results',
            null,
            null
        );

        $dispatcher->process($connector);
        $result = $connector->getResponseData();

        return $result[$requestPendingPartial->getServerHash()];
    }

    //####################################

    private function getFailedMessage()
    {
        $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
        $message->initFromPreparedData(
            'Request wait timeout exceeded.',
            Message::TYPE_ERROR
        );

        return $message;
    }

    private function completeRequest(Partial $requestPendingPartial, array $messages = [])
    {
        $requestPendingPartial->setSettings('result_messages', $messages);
        $requestPendingPartial->setData('next_part', null);
        $requestPendingPartial->setData('is_completed', 1);

        $requestPendingPartial->save();
    }

    //####################################
}
